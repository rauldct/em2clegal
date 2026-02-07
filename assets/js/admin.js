/**
 * Admin Panel - JS
 * EMC2 Legal CMS
 */

document.addEventListener('DOMContentLoaded', function() {

    // --- Sidebar Toggle (mobile) ---
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }

    // --- Auto-generate slug from title ---
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    if (titleInput && slugInput) {
        let slugManuallyEdited = slugInput.value.length > 0;

        slugInput.addEventListener('input', function() {
            slugManuallyEdited = true;
        });

        titleInput.addEventListener('input', function() {
            if (!slugManuallyEdited || slugInput.value === '') {
                slugInput.value = slugify(this.value);
                slugManuallyEdited = false;
            }
        });
    }

    // --- SEO character counters ---
    const metaTitle = document.getElementById('meta_title');
    const metaTitleCount = document.getElementById('metaTitleCount');
    const metaDesc = document.getElementById('meta_description');
    const metaDescCount = document.getElementById('metaDescCount');
    const seoPreviewTitle = document.getElementById('seoPreviewTitle');
    const seoPreviewDesc = document.getElementById('seoPreviewDesc');

    if (metaTitle && metaTitleCount) {
        function updateMetaTitleCount() {
            metaTitleCount.textContent = metaTitle.value.length + '/70';
            if (seoPreviewTitle) seoPreviewTitle.textContent = metaTitle.value || titleInput?.value || 'Título del artículo';
        }
        metaTitle.addEventListener('input', updateMetaTitleCount);
        updateMetaTitleCount();
    }

    if (metaDesc && metaDescCount) {
        function updateMetaDescCount() {
            metaDescCount.textContent = metaDesc.value.length + '/170';
            if (seoPreviewDesc) seoPreviewDesc.textContent = metaDesc.value || 'Descripción del artículo...';
        }
        metaDesc.addEventListener('input', updateMetaDescCount);
        updateMetaDescCount();
    }

    // Also update SEO preview when title changes
    if (titleInput && seoPreviewTitle) {
        titleInput.addEventListener('input', function() {
            if (!metaTitle || !metaTitle.value) {
                seoPreviewTitle.textContent = this.value || 'Título del artículo';
            }
        });
    }

    // --- Autosave ---
    if (typeof POST_ID !== 'undefined' && POST_ID > 0) {
        setInterval(function() {
            autoSave();
        }, 60000); // cada 60 segundos
    }

    function autoSave() {
        const title = document.getElementById('title')?.value;
        const content = typeof tinymce !== 'undefined' ? tinymce.activeEditor?.getContent() : '';
        const excerpt = document.getElementById('excerpt')?.value;
        const status = document.getElementById('autosaveStatus');

        if (!title) return;

        fetch(ADMIN_URL + '/ajax/autosave.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify({
                id: POST_ID,
                title: title,
                content: content,
                excerpt: excerpt
            })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && status) {
                status.textContent = 'Guardado a las ' + data.saved_at;
            }
        })
        .catch(function() {});
    }

    // --- AI Article Generator ---
    var aiModal = document.getElementById('aiModal');
    var btnAiGenerate = document.getElementById('btnAiGenerate');
    var closeAiModal = document.getElementById('closeAiModal');

    if (btnAiGenerate && aiModal) {
        var aiStep1 = document.getElementById('aiStep1');
        var aiStep2 = document.getElementById('aiStep2');
        var aiStep3 = document.getElementById('aiStep3');
        var aiError = document.getElementById('aiError');
        var aiTopic = document.getElementById('aiTopic');
        var aiCategory = document.getElementById('aiCategory');
        var aiTitlesList = document.getElementById('aiTitlesList');
        var aiSuggestBtn = document.getElementById('aiSuggestBtn');
        var aiGenerateBtn = document.getElementById('aiGenerateBtn');
        var aiBackBtn = document.getElementById('aiBackBtn');
        var aiRetryBtn = document.getElementById('aiRetryBtn');
        var aiErrorText = document.getElementById('aiErrorText');
        var aiLoadingText = document.getElementById('aiLoadingText');

        var selectedTitle = null;
        var selectedMeta = null;

        function showAiStep(step) {
            aiStep1.style.display = step === 1 ? '' : 'none';
            aiStep2.style.display = step === 2 ? '' : 'none';
            aiStep3.style.display = step === 3 ? '' : 'none';
            aiError.style.display = step === 'error' ? '' : 'none';
        }

        function showAiError(msg) {
            aiErrorText.textContent = msg;
            showAiStep('error');
        }

        btnAiGenerate.addEventListener('click', function() {
            aiModal.classList.add('active');
            showAiStep(1);
            aiTopic.value = '';
            selectedTitle = null;
            selectedMeta = null;
        });

        closeAiModal.addEventListener('click', function() {
            aiModal.classList.remove('active');
        });

        aiModal.addEventListener('click', function(e) {
            if (e.target === aiModal) aiModal.classList.remove('active');
        });

        // Step 1 → Step 2: Suggest titles
        aiSuggestBtn.addEventListener('click', function() {
            var topic = aiTopic.value.trim();
            if (!topic) { aiTopic.focus(); return; }

            showAiStep(3);
            aiLoadingText.textContent = 'Generando títulos SEO...';
            document.querySelector('#aiStep3 small').textContent = 'Analizando keywords y tendencias de búsqueda...';

            fetch(ADMIN_URL + '/ajax/ai-generate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRF_TOKEN
                },
                body: JSON.stringify({ action: 'suggest_titles', topic: topic })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    showAiError(data.error || 'Error al generar títulos.');
                    return;
                }

                aiTitlesList.innerHTML = '';
                selectedTitle = null;
                selectedMeta = null;
                aiGenerateBtn.disabled = true;

                data.titles.forEach(function(item, idx) {
                    var div = document.createElement('div');
                    div.className = 'ai-title-option';
                    div.innerHTML = '<h4>' + escapeHtml(item.title) + '</h4><p>' + escapeHtml(item.meta_description) + '</p>';
                    div.addEventListener('click', function() {
                        document.querySelectorAll('.ai-title-option').forEach(function(el) { el.classList.remove('selected'); });
                        div.classList.add('selected');
                        selectedTitle = item.title;
                        selectedMeta = item.meta_description;
                        aiGenerateBtn.disabled = false;
                    });
                    aiTitlesList.appendChild(div);
                });

                showAiStep(2);
            })
            .catch(function() { showAiError('Error de conexión con el servidor.'); });
        });

        // Back to step 1
        aiBackBtn.addEventListener('click', function() {
            showAiStep(1);
        });

        // Step 2 → Step 3: Generate article
        aiGenerateBtn.addEventListener('click', function() {
            if (!selectedTitle) return;

            showAiStep(3);
            aiLoadingText.textContent = 'Escribiendo artículo completo...';
            document.querySelector('#aiStep3 small').textContent = 'Esto puede tardar entre 30-60 segundos';

            fetch(ADMIN_URL + '/ajax/ai-generate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRF_TOKEN
                },
                body: JSON.stringify({
                    action: 'generate_article',
                    title: selectedTitle,
                    meta_description: selectedMeta,
                    category_id: aiCategory ? parseInt(aiCategory.value) || 0 : 0
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    showAiError(data.error || 'Error al generar el artículo.');
                    return;
                }

                // Redirect to editor
                window.location.href = data.redirect;
            })
            .catch(function() { showAiError('Error de conexión con el servidor.'); });
        });

        // Retry on error
        aiRetryBtn.addEventListener('click', function() {
            showAiStep(1);
        });

        // Allow Enter key in topic input
        aiTopic.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                aiSuggestBtn.click();
            }
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // --- Delete Post ---
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            var title = this.dataset.title;
            if (confirm('¿Eliminar "' + title + '"? Esta acción no se puede deshacer.')) {
                fetch(ADMIN_URL + '/ajax/delete-post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({ id: parseInt(id) })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        btn.closest('tr').remove();
                    } else {
                        alert('Error: ' + (data.error || 'No se pudo eliminar'));
                    }
                })
                .catch(function() { alert('Error de conexión'); });
            }
        });
    });

    // --- Media Upload (drag & drop) ---
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');

    if (uploadZone && fileInput) {
        ['dragenter', 'dragover'].forEach(function(ev) {
            uploadZone.addEventListener(ev, function(e) {
                e.preventDefault();
                uploadZone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function(ev) {
            uploadZone.addEventListener(ev, function(e) {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
            });
        });

        uploadZone.addEventListener('drop', function(e) {
            var files = e.dataTransfer.files;
            for (var i = 0; i < files.length; i++) {
                uploadImage(files[i]);
            }
        });

        uploadZone.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            for (var i = 0; i < this.files.length; i++) {
                uploadImage(this.files[i]);
            }
        });
    }

    // --- Media Modal for post editor ---
    const selectFeaturedBtn = document.getElementById('selectFeaturedImage');
    const removeFeaturedBtn = document.getElementById('removeFeaturedImage');
    const mediaModal = document.getElementById('mediaModal');
    const closeModalBtn = document.getElementById('closeMediaModal');
    const featuredInput = document.getElementById('featured_image');
    const featuredPreview = document.getElementById('featuredImagePreview');

    if (selectFeaturedBtn && mediaModal) {
        selectFeaturedBtn.addEventListener('click', function() {
            mediaModal.classList.add('active');
            loadMediaGrid();
            // Load Unsplash images directly
            var ug = document.getElementById('unsplashGrid');
            if (ug) loadUnsplashImages();
        });

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                mediaModal.classList.remove('active');
            });
        }

        mediaModal.addEventListener('click', function(e) {
            if (e.target === mediaModal) mediaModal.classList.remove('active');
        });
    }

    if (removeFeaturedBtn) {
        removeFeaturedBtn.addEventListener('click', function() {
            if (featuredInput) featuredInput.value = '';
            if (featuredPreview) featuredPreview.innerHTML = '';
            removeFeaturedBtn.style.display = 'none';
        });
    }

    // Media upload in modal
    var modalFileInput = document.getElementById('mediaFileInput');
    var mediaUploadArea = document.getElementById('mediaUploadArea');

    if (mediaUploadArea && modalFileInput) {
        mediaUploadArea.addEventListener('click', function() {
            modalFileInput.click();
        });

        ['dragenter', 'dragover'].forEach(function(ev) {
            mediaUploadArea.addEventListener(ev, function(e) { e.preventDefault(); });
        });

        mediaUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            var files = e.dataTransfer.files;
            for (var i = 0; i < files.length; i++) {
                uploadImage(files[i], function(url) { selectImageForFeatured(url); });
            }
        });

        modalFileInput.addEventListener('change', function() {
            for (var i = 0; i < this.files.length; i++) {
                uploadImage(this.files[i], function(url) { selectImageForFeatured(url); });
            }
        });
    }

    function selectImageForFeatured(url) {
        if (featuredInput) featuredInput.value = url;
        if (featuredPreview) featuredPreview.innerHTML = '<img src="' + url + '" alt="Imagen destacada" style="max-width:100%; border-radius:6px;">';
        if (removeFeaturedBtn) removeFeaturedBtn.style.display = '';
        if (mediaModal) mediaModal.classList.remove('active');
    }

    function loadMediaGrid() {
        var grid = document.getElementById('mediaGrid');
        if (!grid) return;
        grid.innerHTML = '';
        // Also load Unsplash suggestions when modal opens
        loadUnsplashImages();
    }

    // --- Unsplash Image Suggestions ---
    var refreshUnsplashBtn = document.getElementById('refreshUnsplash');
    var unsplashGrid = document.getElementById('unsplashGrid');
    var unsplashLoading = document.getElementById('unsplashLoading');

    // 120 verified Unsplash photo IDs organized by theme
    var unsplashByTheme = {
        legal: [
            'photo-1589829545856-d10d557cf95f','photo-1505664194779-8beaceb93744','photo-1589994965851-a8f479c573a1',
            'photo-1436450412740-6b988f486c6b','photo-1521587760476-6c12a4b040da','photo-1450101499163-c8848e968f44',
            'photo-1479142506502-19b3a3b7ff33','photo-1575505586569-646b2ca898fc','photo-1568992687947-868a62a9f521',
            'photo-1593115057322-e94b77572f20','photo-1507679799987-c73779587ccf','photo-1453945619913-79ec89a82c51'
        ],
        documentos: [
            'photo-1554224155-6726b3ff858f','photo-1587825140708-dfaf18c1b6dc','photo-1542744094-3a31f272c490',
            'photo-1568057373517-430e508e46e3','photo-1618044733300-9472054094ee','photo-1586282391129-76a6df230234',
            'photo-1450101499163-c8848e968f44','photo-1632406897798-89de7e8963e2','photo-1633526543814-9718c8922b7a',
            'photo-1611532736597-de2d4265fba3','photo-1584727638096-042c45049ebe','photo-1562564055-71e051d33c19'
        ],
        espana: [
            'photo-1543783207-ec64e4d95325','photo-1539037116277-4db20889f2d7','photo-1559386484-97dfc0e15539',
            'photo-1509840841025-9088ba78a826','photo-1558618666-fcd25c85f82e','photo-1504019347908-b45f9b0b8dd5',
            'photo-1509003092362-0fff7895c2a0','photo-1511527661048-7fe73d85e9a4','photo-1552832230-c0197dd311b5',
            'photo-1570698473651-b2de5fc46263','photo-1562883676-8c7feb83f09b','photo-1583422409516-2895a77efded'
        ],
        familia: [
            'photo-1527525443983-6e60c75fff46','photo-1511895426328-dc8714191300','photo-1529156069898-49953e39b3ac',
            'photo-1606092195730-5d7b9af1efc5','photo-1581952976147-5a2d15560349','photo-1609220136736-443140cffec6',
            'photo-1559734840-f9509ee5677f','photo-1600880292203-757bb62b4baf','photo-1598811629009-9ca4978b1e89',
            'photo-1574158622682-e40e69881006','photo-1475503572774-15a45e5d60b9','photo-1591604466107-ec97de577aff'
        ],
        oficina: [
            'photo-1497366216548-37526070297c','photo-1507003211169-0a1dd7228f2d','photo-1551836022-d5d88e9218df',
            'photo-1497215842964-222b430dc094','photo-1552664730-d307ca884978','photo-1560472355-536de3962603',
            'photo-1524758631624-e2822e304c36','photo-1553877522-43269d4ea984','photo-1542744173-8e7e91415657',
            'photo-1504384308090-c894fdcc538d','photo-1517245386747-bb3f6321d58e','photo-1531973576160-7125cd663d86'
        ],
        gobierno: [
            'photo-1555848962-6e79363ec58f','photo-1575517111478-7f6afd0973db','photo-1541872703-74c5e44368f9',
            'photo-1524492412937-b28074a5d7da','photo-1517048676732-d65bc937f952','photo-1603899122361-e99b4f6fecf5',
            'photo-1569025743873-ea3a9ber528f0','photo-1577962917302-cd874462e648','photo-1590076215667-875c2de0a9b1',
            'photo-1523292562811-8fa7962a78c8','photo-1569025690938-a00729c9e1f3','photo-1558618666-fcd25c85f82e'
        ],
        viaje: [
            'photo-1488646953014-85cb44e25828','photo-1469854523086-cc02fe5d8800','photo-1507525428034-b723cf961d3e',
            'photo-1476514525535-07fb3b4ae5f1','photo-1530521954074-e64f6810b32d','photo-1500835556837-99ac94a94552',
            'photo-1473625247510-8ceb1760943f','photo-1503220317375-aaad61436b1b','photo-1501785888041-af3ef285b470',
            'photo-1539635278303-d4002c07eae3','photo-1504150558240-0b4fd8946624','photo-1517760444937-f6397edcbbcd'
        ],
        estudio: [
            'photo-1523050854058-8df90110c9f1','photo-1541339907198-e08756dedf3f','photo-1519452635265-7b1fbfd1e4e0',
            'photo-1498243691581-b145c3f54a5a','photo-1524178232363-1fb2b075b655','photo-1427504494785-3a9ca7044f45',
            'photo-1503676260728-1c00da094a0b','photo-1509062522246-3755977927d7','photo-1562774053-701939374585',
            'photo-1606761568499-6d2451b23c66','photo-1517486808906-6ca8b3f04846','photo-1577896851231-70ef18881754'
        ],
        trabajo: [
            'photo-1521791136064-7986c2920216','photo-1454165804606-c3d57bc86b40','photo-1542744173-05336fcc7ad4',
            'photo-1486312338219-ce68d2c6f44d','photo-1553028826-f4804a6dba3b','photo-1557804506-669a67965ba0',
            'photo-1573497019940-1c28c88b4f3e','photo-1560472354-b33ff0c44a43','photo-1523580494863-6f3031224c94',
            'photo-1600880292089-90a7e086ee0c','photo-1542626991-cbc4e32524cc','photo-1559136555-9303baea8ebd'
        ],
        personas: [
            'photo-1529156069898-49953e39b3ac','photo-1517486808906-6ca8b3f04846','photo-1573497019940-1c28c88b4f3e',
            'photo-1573164713714-d95e436ab8d6','photo-1522202176988-66273c2fd55f','photo-1531123897727-8f129e1688ce',
            'photo-1517841905240-472988babdf9','photo-1507003211169-0a1dd7228f2d','photo-1544005313-94ddf0286df2',
            'photo-1506794778202-cad84cf45f1d','photo-1534528741775-53994a69daeb','photo-1488426862026-3ee34a7d66df'
        ]
    };

    // Keywords mapping to themes
    var themeKeywords = {
        legal: ['legal','ley','leyes','abogado','abogados','juridico','derecho','penal','defensa','juicio','tribunal','sentencia'],
        documentos: ['documento','documentos','nie','tie','tarjeta','permiso','certificado','pasaporte','solicitud','tramite','tramites','renovar','renovacion','formulario'],
        espana: ['españa','espana','madrid','barcelona','spanish','espanol','europa','europeo'],
        familia: ['familia','familiar','reagrupacion','reagrupar','hijos','padres','matrimonio','conyuge','divorcio','custodia'],
        oficina: ['oficina','consulta','asesoria','despacho','gestion','profesional','contrato','laboral'],
        gobierno: ['gobierno','extranjeria','administracion','oficina','comisaria','policia','resolucion','reglamento','reforma','legislacion','normativa'],
        viaje: ['visado','visa','viaje','viajar','entrada','frontera','vuelo','pais','consular','consulado'],
        estudio: ['estudiante','estudio','estudiar','universidad','formacion','curso','beca','academico','educacion'],
        trabajo: ['trabajo','trabajar','empleo','cuenta','ajena','autonomo','empresa','contratacion','laboral','nomina','cotizacion'],
        personas: ['arraigo','social','integracion','residencia','nacionalidad','ciudadania','inmigrante','extranjero','regularizacion']
    };

    function getImagesForTitle() {
        var titleEl = document.getElementById('title');
        var title = titleEl ? titleEl.value.trim().toLowerCase() : '';
        var matched = [];

        if (title.length > 3) {
            // Score each theme by keyword matches
            var scores = {};
            for (var theme in themeKeywords) {
                scores[theme] = 0;
                themeKeywords[theme].forEach(function(kw) {
                    if (title.indexOf(kw) !== -1) scores[theme] += 2;
                });
            }
            // Get top 3 scoring themes
            var sorted = Object.keys(scores).sort(function(a,b) { return scores[b] - scores[a]; });
            var topThemes = sorted.filter(function(t) { return scores[t] > 0; }).slice(0, 3);

            if (topThemes.length === 0) topThemes = ['legal', 'documentos', 'espana'];

            topThemes.forEach(function(t) {
                matched = matched.concat(unsplashByTheme[t] || []);
            });
        }

        if (matched.length === 0) {
            // Combine all themes
            for (var t in unsplashByTheme) {
                matched = matched.concat(unsplashByTheme[t]);
            }
        }

        // Remove duplicates and shuffle
        matched = matched.filter(function(v, i, a) { return a.indexOf(v) === i; });
        for (var i = matched.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var tmp = matched[i]; matched[i] = matched[j]; matched[j] = tmp;
        }
        return matched.slice(0, 6);
    }

    function loadUnsplashImages() {
        var grid = document.getElementById('unsplashGrid');
        if (!grid) return;

        var photos = getImagesForTitle();
        grid.innerHTML = '';

        photos.forEach(function(photoId) {
            var thumbUrl = 'https://images.unsplash.com/' + photoId + '?auto=format&fit=crop&w=400&h=260&q=60';
            var fullUrl = 'https://images.unsplash.com/' + photoId + '?auto=format&fit=crop&w=1200&h=750&q=80';

            var item = document.createElement('div');
            item.className = 'unsplash-item';
            item.innerHTML = '<img src="' + thumbUrl + '" alt="Imagen sugerida" loading="lazy"><div class="unsplash-credit">Unsplash</div>';

            item.addEventListener('click', (function(url) {
                return function() { downloadUnsplashImage(url); };
            })(fullUrl));

            grid.appendChild(item);
        });
    }

    function downloadUnsplashImage(imageUrl) {
        var grid = document.getElementById('unsplashGrid');
        var loading = document.getElementById('unsplashLoading');
        if (!grid || !loading) return;

        grid.style.display = 'none';
        loading.style.display = '';

        fetch(ADMIN_URL + '/ajax/unsplash-download.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify({ url: imageUrl })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            grid.style.display = '';
            loading.style.display = 'none';

            if (data.success) {
                selectImageForFeatured(data.url);
            } else {
                alert('Error: ' + (data.error || 'No se pudo descargar la imagen.'));
            }
        })
        .catch(function() {
            grid.style.display = '';
            loading.style.display = 'none';
            alert('Error de conexión.');
        });
    }

    if (refreshUnsplashBtn) {
        refreshUnsplashBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loadUnsplashImages();
        });
    }

    function uploadImage(file, callback) {
        var formData = new FormData();
        formData.append('image', file);

        var progress = document.getElementById('uploadProgress');
        var progressFill = document.getElementById('progressFill');
        var uploadStatus = document.getElementById('uploadStatus');

        if (progress) progress.style.display = 'block';
        if (progressFill) progressFill.style.width = '30%';
        if (uploadStatus) uploadStatus.textContent = 'Subiendo ' + file.name + '...';

        fetch(ADMIN_URL + '/ajax/upload-image.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (progressFill) progressFill.style.width = '100%';
            if (data.success) {
                if (uploadStatus) uploadStatus.textContent = 'Subida completada';
                if (callback) {
                    callback(data.url);
                } else {
                    // Refresh page to show new image
                    setTimeout(function() { location.reload(); }, 500);
                }
            } else {
                if (uploadStatus) uploadStatus.textContent = 'Error: ' + data.error;
            }
            setTimeout(function() {
                if (progress) progress.style.display = 'none';
                if (progressFill) progressFill.style.width = '0';
            }, 2000);
        })
        .catch(function() {
            if (uploadStatus) uploadStatus.textContent = 'Error de conexión';
        });
    }
});

/**
 * Generar slug a partir de texto (versión JS)
 */
function slugify(text) {
    var replacements = {
        'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u',
        'ñ': 'n', 'ü': 'u', 'ç': 'c',
        'Á': 'a', 'É': 'e', 'Í': 'i', 'Ó': 'o', 'Ú': 'u',
        'Ñ': 'n', 'Ü': 'u'
    };
    text = text.toLowerCase();
    for (var key in replacements) {
        text = text.split(key).join(replacements[key]);
    }
    return text
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}
