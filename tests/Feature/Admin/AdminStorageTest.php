<?php

use App\Models\Folder;
use App\Models\Image;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    config(['media.disk' => 'local']);
    $this->admin = User::factory()->create(['is_admin' => true]);
});

function adminStorageLibraryFile(User $owner, string $name = 'foto.png', ?Folder $folder = null): Image
{
    $path = 'user_images/'.$owner->id.'/'.$name;
    Storage::disk('public')->put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jCusAAAAASUVORK5CYII='));

    return Image::create([
        'user_id' => $owner->id,
        'folder_id' => $folder?->id,
        'path' => $path,
        'original_name' => $name,
        'size' => 1024,
    ]);
}

test('all storage routes require an authenticated administrator', function () {
    $id = str_repeat('a', 64);
    $routes = [
        ['get', route('adm.storage.index')],
        ['get', route('adm.storage.show', $id)],
        ['delete', route('adm.storage.destroy', $id)],
        ['delete', route('adm.storage.bulk-destroy')],
    ];
    foreach ($routes as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }
    $user = User::factory()->create(['is_admin' => false]);
    foreach ($routes as [$method, $url]) {
        $this->actingAs($user)->{$method}($url)->assertRedirect('/dashboard');
    }
});

test('inventory includes library and whatsapp files but excludes application and internal files', function () {
    $owner = User::factory()->create(['name' => 'Dona da biblioteca']);
    adminStorageLibraryFile($owner, 'minha-foto.png');
    Storage::disk('public')->put('user_images/'.$owner->id.'/sem-registro.pdf', 'orphan');
    Storage::disk('public')->put('user_images/99999/sem-usuario.jpg', 'orphan');
    Storage::disk('public')->put('homepage/site.png', 'site');
    Storage::disk('public')->put('agency-settings/1/logo.png', 'logo');
    Storage::disk('local')->put('interno.txt', 'private');
    Storage::disk('local')->put('uazapi_media/audio.ogg', 'audio');
    Storage::disk('local')->put('evolution_oficial_media/documento.pdf', 'pdf');
    Storage::disk('local')->put('whatsapp_cloud_media/cloud.jpg', 'image');

    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    $response->assertOk()->assertSeeText('Dona da biblioteca')->assertSeeText('Usuário não identificado');
    expect($response->viewData('files')->total())->toBe(6);
    expect(collect($response->viewData('files')->items())->pluck('name')->all())
        ->toContain('minha-foto.png', 'sem-registro.pdf', 'sem-usuario.jpg', 'audio.ogg', 'documento.pdf', 'cloud.jpg')
        ->not->toContain('site.png', 'logo.png', 'interno.txt');
});

test('user filter includes files assigned by record or directory and unknown filter includes whatsapp', function () {
    $owner = User::factory()->create();
    adminStorageLibraryFile($owner);
    Storage::disk('public')->put('user_images/'.$owner->id.'/sem-registro.png', 'orphan');
    Storage::disk('local')->put('uazapi_media/audio.ogg', 'audio');

    $response = $this->actingAs($this->admin)->get(route('adm.storage.index', ['user' => (string) $owner->id]));
    $response->assertOk();
    expect($response->viewData('files')->total())->toBe(2);
    $response = $this->get(route('adm.storage.index', ['user' => 'unknown']));
    $response->assertOk();
    expect($response->viewData('files')->total())->toBe(1);
    expect($response->viewData('files')->items()[0]['name'])->toBe('audio.ogg');
});

test('date filters use inclusive modified dates in Brasilia and support either boundary alone', function () {
    foreach (['before' => '2026-10-29T02:59:59Z', 'start' => '2026-10-29T03:00:00Z', 'end' => '2026-10-30T02:59:59Z', 'after' => '2026-10-30T03:00:00Z'] as $name => $date) {
        $path = 'uazapi_media/'.$name.'.jpg';
        Storage::disk('local')->put($path, $name);
        touch(Storage::disk('local')->path($path), strtotime($date));
    }
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index', ['date_from' => '2026-10-29', 'date_to' => '2026-10-29']));
    $response->assertOk();
    expect(collect($response->viewData('files')->items())->pluck('name')->all())->toEqualCanonicalizing(['start.jpg', 'end.jpg']);
    $response = $this->get(route('adm.storage.index', ['date_to' => '2026-10-29']));
    $response->assertOk();
    expect($response->viewData('files')->total())->toBe(3);
    $response = $this->get(route('adm.storage.index', ['date_from' => '2026-10-30']));
    expect($response->viewData('files')->total())->toBe(1);
    $this->get(route('adm.storage.index', ['date_from' => '2026-10-30', 'date_to' => '2026-10-29']))
        ->assertSessionHasErrors('date_to');
});

test('view streams private media through authenticated route and downloads active content', function () {
    Storage::disk('local')->put('uazapi_media/documento.html', '<html>teste</html>');
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    $file = $response->viewData('files')->items()[0];
    $view = $this->get(route('adm.storage.show', ['file' => $file['id'], 'snapshot' => $response->viewData('snapshotToken')]));
    $view->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($view->headers->get('Content-Disposition'))->toStartWith('attachment;');
    expect($view->streamedContent())->toBe('<html>teste</html>');
});

test('view displays images inline', function () {
    adminStorageLibraryFile($this->admin);
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    $file = $response->viewData('files')->items()[0];
    $view = $this->get(route('adm.storage.show', ['file' => $file['id'], 'snapshot' => $response->viewData('snapshotToken')]));
    $view->assertOk()->assertHeader('Content-Type', 'image/png');
    expect($view->headers->get('Content-Disposition'))->toStartWith('inline;');
});

test('individual deletion removes library record and recalculates user and folder usage', function () {
    $owner = User::factory()->create(['storage_used_mb' => 2]);
    $folder = Folder::create(['user_id' => $owner->id, 'name' => 'Pasta', 'storage_used_mb' => 2]);
    $image = adminStorageLibraryFile($owner, 'excluir.png', $folder);
    adminStorageLibraryFile($owner, 'manter.png', $folder);
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    $file = collect($response->viewData('files')->items())->firstWhere('name', 'excluir.png');
    $this->delete(route('adm.storage.destroy', $file['id']), ['snapshot' => $response->viewData('snapshotToken')])
        ->assertRedirect(route('adm.storage.index'))->assertSessionHas('success');
    Storage::disk('public')->assertMissing($image->path);
    $this->assertDatabaseMissing('images', ['id' => $image->id]);
    expect($owner->fresh()->storage_used_mb)->toBe(1);
    expect($folder->fresh()->storage_used_mb)->toBe(1);
});

test('select all deletes all filtered pages except unchecked files and excludes newly arrived files', function () {
    for ($i = 0; $i < 53; $i++) {
        Storage::disk('local')->put("uazapi_media/{$i}.ogg", 'audio');
    }
    $owner = User::factory()->create();
    $image = adminStorageLibraryFile($owner);
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index', ['user' => 'unknown']));
    $token = $response->viewData('snapshotToken');
    $keep = $response->viewData('files')->items()[0];
    expect($response->viewData('files')->total())->toBe(53);
    expect($response->viewData('files')->items())->toHaveCount(50);
    $page2 = $this->get(route('adm.storage.index', ['user' => 'unknown', 'snapshot' => $token, 'page' => 2]));
    expect($page2->viewData('snapshotToken'))->toBe($token);
    expect($page2->viewData('files')->items())->toHaveCount(3);
    Storage::disk('local')->put('uazapi_media/novo.ogg', 'new');

    $this->delete(route('adm.storage.bulk-destroy'), [
        'snapshot' => $token, 'mode' => 'all', 'selection' => json_encode([$keep['id']]),
    ])->assertRedirect(route('adm.storage.index', ['user' => 'unknown']))->assertSessionHas('success', '52 arquivo(s) excluído(s).');
    expect(Storage::disk('local')->allFiles('uazapi_media'))->toHaveCount(2);
    Storage::disk('local')->assertExists([$keep['path'], 'uazapi_media/novo.ogg']);
    Storage::disk('public')->assertExists($image->path);
});

test('manual bulk selection deletes only selected files', function () {
    Storage::disk('local')->put('uazapi_media/a.ogg', 'a');
    Storage::disk('local')->put('uazapi_media/b.ogg', 'b');
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    $selected = $response->viewData('files')->items()[0];
    $this->delete(route('adm.storage.bulk-destroy'), [
        'snapshot' => $response->viewData('snapshotToken'), 'mode' => 'selected', 'selection' => json_encode([$selected['id']]),
    ])->assertSessionHas('success', '1 arquivo(s) excluído(s).');
    Storage::disk('local')->assertMissing($selected['path']);
    expect(Storage::disk('local')->allFiles('uazapi_media'))->toHaveCount(1);
});

test('storage deletion failure preserves the library record and usage counters', function () {
    $this->admin->update(['storage_used_mb' => 1]);
    $image = adminStorageLibraryFile($this->admin);
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    $file = $response->viewData('files')->items()[0];
    $disk = Storage::disk('public');
    $failing = Mockery::mock($disk)->makePartial();
    $failing->shouldReceive('delete')->with($image->path)->once()->andReturn(false);
    Storage::set('public', $failing);

    $this->delete(route('adm.storage.destroy', $file['id']), ['snapshot' => $response->viewData('snapshotToken')])
        ->assertSessionHas('error')->assertSessionMissing('success');
    $this->assertDatabaseHas('images', ['id' => $image->id]);
    expect($this->admin->fresh()->storage_used_mb)->toBe(1);
    expect($disk->exists($image->path))->toBeTrue();
});

test('partial bulk failure reports both outcomes and preserves the failed file', function () {
    Storage::disk('local')->put('uazapi_media/a.ogg', 'audio');
    Storage::disk('local')->put('uazapi_media/b.ogg', 'audio');
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    Storage::disk('local')->put('uazapi_media/b.ogg', 'modified file');
    clearstatcache();
    $this->delete(route('adm.storage.bulk-destroy'), [
        'snapshot' => $response->viewData('snapshotToken'), 'mode' => 'all', 'selection' => '[]',
    ])->assertSessionHas('success', '1 arquivo(s) excluído(s).')->assertSessionHas('error');
    Storage::disk('local')->assertMissing('uazapi_media/a.ogg');
    Storage::disk('local')->assertExists('uazapi_media/b.ogg');
});

test('whatsapp inventory uses the configured media disk even when it is public', function () {
    config(['media.disk' => 'public']);
    Storage::disk('public')->put('whatsapp_cloud_media/audio.ogg', 'audio');
    Storage::disk('local')->put('whatsapp_cloud_media/not-current.ogg', 'old');
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    expect($response->viewData('files')->total())->toBe(1);
    $file = $response->viewData('files')->items()[0];
    expect($file['disk'])->toBe('public');
    $this->delete(route('adm.storage.destroy', $file['id']), ['snapshot' => $response->viewData('snapshotToken')])
        ->assertSessionHas('success');
    Storage::disk('public')->assertMissing('whatsapp_cloud_media/audio.ogg');
    Storage::disk('local')->assertExists('whatsapp_cloud_media/not-current.ogg');
});

test('snapshot cannot be used by another admin and expired snapshot does not delete anything', function () {
    Storage::disk('local')->put('uazapi_media/a.ogg', 'audio');
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    $token = $response->viewData('snapshotToken');
    $file = $response->viewData('files')->items()[0];
    $other = User::factory()->create(['is_admin' => true]);
    $this->actingAs($other)->delete(route('adm.storage.destroy', $file['id']), ['snapshot' => $token])
        ->assertSessionHasErrors('snapshot');
    $this->actingAs($this->admin);
    $this->travel(31)->minutes();
    $this->delete(route('adm.storage.destroy', $file['id']), ['snapshot' => $token])
        ->assertSessionHasErrors('snapshot');
    Storage::disk('local')->assertExists($file['path']);
});

test('tampered and empty selections cannot delete files', function () {
    Storage::disk('local')->put('uazapi_media/a.ogg', 'audio');
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    $token = $response->viewData('snapshotToken');
    foreach (['[]', '["../../.env"]', '[null]', '{}'] as $selection) {
        $this->delete(route('adm.storage.bulk-destroy'), [
            'snapshot' => $token, 'mode' => 'selected', 'selection' => $selection,
        ])->assertSessionHasErrors('selection');
    }
    $this->get(route('adm.storage.show', ['file' => 'forged', 'snapshot' => $token]))->assertNotFound();
    Storage::disk('local')->assertExists('uazapi_media/a.ogg');
});

test('changed and missing files are skipped and failures are reported without deleting library records', function () {
    $image = adminStorageLibraryFile($this->admin);
    Storage::disk('local')->put('uazapi_media/a.ogg', 'audio');
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    Storage::disk('public')->put($image->path, 'file replaced with different size');
    Storage::disk('local')->delete('uazapi_media/a.ogg');
    clearstatcache();
    $this->delete(route('adm.storage.bulk-destroy'), [
        'snapshot' => $response->viewData('snapshotToken'), 'mode' => 'all', 'selection' => '[]',
    ])->assertSessionHas('error')->assertSessionMissing('success');
    $this->assertDatabaseHas('images', ['id' => $image->id]);
    Storage::disk('public')->assertExists($image->path);
});

test('changing filters creates a different snapshot and ignores old selections', function () {
    $owner = User::factory()->create();
    adminStorageLibraryFile($owner);
    Storage::disk('local')->put('uazapi_media/a.ogg', 'audio');
    $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
    $token = $response->viewData('snapshotToken');
    $filtered = $this->get(route('adm.storage.index', ['snapshot' => $token, 'user' => 'unknown']));
    expect($filtered->viewData('snapshotToken'))->not->toBe($token);
    expect($filtered->viewData('files')->total())->toBe(1);
});

test('symlinks outside media directories cannot be listed or accessed', function () {
    Storage::disk('local')->put('internal/secret.txt', 'secret');
    Storage::disk('local')->makeDirectory('uazapi_media');
    $link = Storage::disk('local')->path('uazapi_media/secret.txt');
    symlink(Storage::disk('local')->path('internal/secret.txt'), $link);
    try {
        $response = $this->actingAs($this->admin)->get(route('adm.storage.index'));
        $response->assertOk();
        expect($response->viewData('files')->total())->toBe(0);
    } finally {
        unlink($link);
    }
});
