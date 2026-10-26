/* 97 Foundation V7.2 — runs before first paint so animated content never flashes. */
(function(d){var h=d.documentElement;h.classList.add('js');try{if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches&&'IntersectionObserver' in window)h.classList.add('js-motion');}catch(e){}setTimeout(function(){h.classList.add('motion-ready');},3500);})(document);
