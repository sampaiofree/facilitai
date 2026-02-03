# Tabelas Pivot

O projeto usa apenas algumas tabelas tipo *pivot* para mapear relacionamentos `many-to-many`. Todas elas são listadas abaixo:

| Tabela | Relacionamentos | Notas |
| --- | --- | --- |
| `chat_tag` | liga `chats` ↔ `tags` | Criada em `database/migrations/2025_02_21_000000_create_tags_tables.php`. Armazena quais tags pertencem a cada chat. |
| `cliente_lead_tag` | liga `cliente_lead` ↔ `tags` | Criada em `database/migrations/2026_02_08_000000_create_cliente_lead_tag_table.php`. Usada para registrar tags aplicadas diretamente aos leads de clientes. |

Se novas relações `many-to-many` forem necessárias, basta seguir o mesmo padrão: criar migration com chaves estrangeiras e definir os métodos `belongsToMany()` nos modelos envolvidos.
