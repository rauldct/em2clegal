/**
 * Blog público - JS
 * EMC2 Legal
 */

document.addEventListener('DOMContentLoaded', function() {

    // --- Barra de progreso de lectura ---
    const progressBar = document.getElementById('readingProgress');
    if (progressBar) {
        const article = document.querySelector('.post-content');
        if (article) {
            window.addEventListener('scroll', function() {
                const articleTop = article.offsetTop;
                const articleHeight = article.offsetHeight;
                const windowHeight = window.innerHeight;
                const scrollPos = window.scrollY;

                const progress = Math.min(100, Math.max(0,
                    ((scrollPos - articleTop + windowHeight * 0.3) / articleHeight) * 100
                ));
                progressBar.style.width = progress + '%';
            });
        }
    }

    // --- Tabla de contenidos automática ---
    const postContent = document.querySelector('.post-content');
    if (postContent) {
        const headings = postContent.querySelectorAll('h2');
        if (headings.length >= 3) {
            const toc = document.createElement('nav');
            toc.className = 'toc';
            const title = document.createElement('h4');
            title.textContent = 'En este artículo';
            toc.appendChild(title);
            const list = document.createElement('ul');

            headings.forEach(function(heading, index) {
                var id = 'section-' + index;
                heading.id = id;
                var li = document.createElement('li');
                var a = document.createElement('a');
                a.href = '#' + id;
                a.textContent = heading.textContent;
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    var offset = heading.getBoundingClientRect().top + window.scrollY - 80;
                    window.scrollTo({ top: offset, behavior: 'smooth' });
                });
                li.appendChild(a);
                list.appendChild(li);
            });

            toc.appendChild(list);
            postContent.insertBefore(toc, postContent.firstChild);

            // Toggle colapsable
            title.addEventListener('click', function() {
                toc.classList.toggle('collapsed');
            });
        }
    }

    // --- Smooth scroll para anclas internas ---
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

});
