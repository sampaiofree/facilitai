<!-- BOTÃO WHATSAPP FIXO -->
<div class="fixed bottom-6 right-6 z-50 flex items-center gap-3">
  
  <!-- Balão de texto -->
  <div id="whatsapp-bubble"
       class="hidden md:flex items-center bg-white text-slate-800 px-4 py-2 rounded-full shadow-lg border border-slate-200 animate-fade-in">
    <span class="text-sm font-medium">
      💬 Fale com a gente no WhatsApp
    </span>
  </div>

  <!-- Botão -->
  <a href="https://wa.me/5527981227636"
     target="_blank"
     aria-label="Fale conosco no WhatsApp"
     class="flex h-16 w-16 items-center justify-center rounded-full bg-green-500 text-white shadow-lg shadow-green-500/40 
            transition-all duration-300 hover:scale-110 hover:bg-green-600 hover:shadow-green-500/60 relative">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.52 3.48A11.81 11.81 0 0012.07 0C5.52 0 .08 5.25 0 11.74a11.66 11.66 0 001.64 6L0 24l6.38-1.67a12.13 12.13 0 005.67 1.44h.05c6.56 0 11.9-5.25 11.96-11.74a11.69 11.69 0 00-3.54-8.55zM12.1 21.54a9.66 9.66 0 01-4.87-1.31l-.35-.21-3.78 1 1-3.67-.23-.38a9.55 9.55 0 01-1.48-5.15c.05-5.3 4.4-9.61 9.71-9.61a9.64 9.64 0 016.9 2.84 9.53 9.53 0 012.86 6.89c-.05 5.3-4.4 9.6-9.76 9.6zm5.45-7.23c-.3-.15-1.78-.88-2.06-.98s-.48-.15-.67.15-.77.97-.94 1.17-.35.22-.65.07a7.95 7.95 0 01-2.34-1.44 8.5 8.5 0 01-1.58-1.95c-.17-.3 0-.46.13-.61.14-.14.3-.35.44-.52s.2-.3.3-.5a.57.57 0 000-.53c-.08-.15-.67-1.62-.92-2.23-.24-.58-.5-.5-.67-.5l-.58-.01a1.1 1.1 0 00-.8.37 3.36 3.36 0 00-1.06 2.5c0 1.47 1.07 2.89 1.22 3.09.15.2 2.1 3.2 5.08 4.49 2.38 1 3.3 1.09 4.48.92.72-.1 2.18-.88 2.5-1.73a2.14 2.14 0 00.15-1.74c-.06-.13-.25-.2-.54-.35z"/>
                        </svg>
  </a>
</div>

<!-- ANIMAÇÃO CSS -->
<style>
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeInUp 0.8s ease-out forwards;
}
</style>

<!-- SCRIPT para ocultar o balão após alguns segundos -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const bubble = document.getElementById("whatsapp-bubble");
  // Exibe o balão com delay (apenas desktop)
  setTimeout(() => {
    if (window.innerWidth > 768) bubble.classList.remove("hidden");
  }, 1000);

  // Oculta automaticamente após 8 segundos
  setTimeout(() => {
    bubble.classList.add("hidden");
  }, 8000);
});
</script>

<footer class="flex flex-col items-center gap-5 bg-slate-900 py-10 text-sm text-white text-opacity-70">
        
        <p>&copy; {{ date('Y') }} FacilitAI. Todos os direitos reservados. | <a href="{{route('politica')}}" class="text-blue-500 transition-colors hover:text-emerald-500">Política de Privacidade</a></p>
    </footer>
    <!-- Banner de Cookies -->
    <div id="cookieBanner" class="fixed bottom-0 left-0 right-0 z-50 bg-slate-900 text-white text-center p-4 shadow-lg hidden">
    <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <p class="text-sm md:text-base text-slate-200">
        Usamos cookies para melhorar sua experiência e analisar o tráfego do site.
        Ao continuar, você concorda com nossa <a href="{{route('politica')}}" class="underline text-emerald-400 hover:text-emerald-300">Política de Privacidade</a>.
        </p>
        <button id="acceptCookies"
                class="bg-emerald-500 text-white px-6 py-2 rounded-full font-semibold text-sm shadow-md hover:bg-emerald-600 transition-all">
        Aceitar e continuar
        </button>
    </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
    const cookieBanner = document.getElementById("cookieBanner");
    const acceptBtn = document.getElementById("acceptCookies");

    // Verifica se o usuário já aceitou
    const cookiesAccepted = localStorage.getItem("cookiesAccepted");

    if (!cookiesAccepted) {
        cookieBanner.classList.remove("hidden");
    }

    // Quando clicar em aceitar
    acceptBtn.addEventListener("click", () => {
        localStorage.setItem("cookiesAccepted", "true");
        cookieBanner.classList.add("hidden");
    });
    });
    </script>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-STYRQNHPKZ"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-STYRQNHPKZ');
</script>

<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "tr4llnpaza");
</script>