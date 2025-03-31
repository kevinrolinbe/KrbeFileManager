Dropzone.autoDiscover = false;

document.addEventListener("DOMContentLoaded", function () {

    // Fonction pour recharger la liste des fichiers en fonction d'un sous-dossier
    function loadFilesList(subFolder) {
        // Construire l'URL de la route file_manager_widget_listfiles avec le paramètre subFolder
        const url = krbeFilemanagerUrlWidgetListfiles + '/' + encodeURI(subFolder || '');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                // Remplacer le contenu de la section #files-list par le nouveau contenu
                const filesListContainer = document.getElementById('files-list');
                // ajout de la valeur subFolder dans l'attribut data-subfolder
                filesListContainer.setAttribute('krbe-filemanager-currentfolder', subFolder);
                filesListContainer.innerHTML = html;

                updateNavigationCurrent(subFolder);
            })
            .catch(error => console.error("Erreur de chargement de la liste de fichiers :", error));
    }
    function loadTree(current) {
        fetch(krbeFilemanagerUrlWidgetListfolders, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.text())
            .then(html => {
                // Remplacer le contenu de la section #files-list par le nouveau contenu
                const treeContainer = document.getElementById('directory-nav-tree');
                treeContainer.innerHTML = html;
                updateNavigationCurrent(current);
            })
            .catch(error => console.error("Erreur de chargement de l'arborescence :", error));
    }
    function updateNavigationCurrent(currentSubFolder) {
        // Suppression de toutes les classes "current"
        document.querySelectorAll('[data-krbe-filemanager] li.current').forEach(function(li) {
            li.classList.remove('current');
        });
        // Chercher l'élément <li> qui contient un lien avec data-krbe-filemanager-folder égal à currentSubFolder
        const navLink = document.querySelector('[data-krbe-filemanager] a[data-krbe-filemanager-folder="' + currentSubFolder + '"]');
        if (navLink) {
            // Ajout de la classe "current" sur le <li> parent
            const li = navLink.closest('li');
            if (li) {
                li.classList.add('current');
            }
        }
        // Mise à jour du champ subFolder dans le formulaire caché
        const subFolderInput = document.querySelector('form#hidden-upload-form input[name="subFolder"]');
        if (subFolderInput) {
            subFolderInput.value = currentSubFolder || '';
        }
    }
    updateNavigationCurrent(subFolder);

    // Utilisation de la délégation pour gérer le clic sur un dossier
    document.body.addEventListener('click', function(e) {
        console.log("Click body détecté, cible :", e.target);
        const link = e.target.closest('a[data-krbe-filemanager-folder]');
        if (link) {
            e.preventDefault();
            const folder = link.getAttribute('data-krbe-filemanager-folder');
            loadFilesList(folder);
        }
    });

    // Initialiser Dropzone sur le formulaire caché
    const hiddenUploadForm = new Dropzone("#hidden-upload-form", {
        autoProcessQueue: true,
        paramName: 'file',
        maxFilesize: 10,
        init: function() {
            this.on("success", function(file, response) {
                console.log('Fichier téléversé:', response.filePath);
                const filesListContainer = document.getElementById('files-list');
                var folder = filesListContainer.getAttribute('krbe-filemanager-currentfolder');
                loadFilesList(folder);
            });
            this.on("error", function(file, errorMessage) {
                let message = translations.error_during_upload;
                if (errorMessage && typeof errorMessage === 'object' && errorMessage.error) {
                    message = errorMessage.error;
                } else if (typeof errorMessage === 'string') {
                    message = errorMessage;
                }
                alert(message);
            });
        }
    });
    // Définir la zone de drop sur le volet droit (files-list)
    const folderContent = document.getElementById('folder-content');
    const dropzoneOverlay = document.getElementById('dropzone-overlay');
    let dragCounter = 0;
    // Afficher l'overlay lorsque l'on glisse un fichier dans la zone de contenu
    folderContent.addEventListener('dragenter', function(e) {
        e.preventDefault();
        dragCounter++;
        dropzoneOverlay.style.display = 'flex';
    });
    folderContent.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dragCounter--;
        if (dragCounter <= 0) {
            dropzoneOverlay.style.display = 'none';
            dragCounter = 0;
        }
    });
    folderContent.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    folderContent.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzoneOverlay.style.display = 'none';
        dragCounter = 0;
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            for (let i = 0; i < files.length; i++) {
                hiddenUploadForm.addFile(files[i]);
            }
        }
    });

    // Recherche
    const searchField = document.getElementById('search');
    if(searchField) {
        console.log("Search field trouvé");
        searchField.addEventListener('keyup', function() {
            console.log("Recherche déclenchée, valeur :", this.value);
            const query = this.value.toLowerCase();
            document.querySelectorAll('.file-item, .folder-item').forEach(function(item) {
                // Vérifier que chaque item contient bien un <p>
                const p = item.querySelector('p');
                if(p) {
                    const filename = p.textContent.toLowerCase();
                    item.style.display = filename.includes(query) ? '' : 'none';
                } else {
                    console.warn("L'élément n'a pas de <p> :", item);
                }
            });
        });
    } else {
        console.error("Champ de recherche introuvable");
    }

    // Toggle view
    const toggleButton = document.getElementById('toggle-view');
    if(toggleButton) {
        console.log("Bouton toggle view trouvé");
        toggleButton.addEventListener('click', function() {
            const filesList = document.getElementById('files-list');
            if(filesList) {
                console.log("Toggle view sur files-list");
                filesList.classList.toggle('list-view');
            }
        });
    } else {
        console.error("Bouton toggle view introuvable");
    }

    // Bouton d'importation déclenche l'ouverture du sélecteur de fichiers
    document.getElementById('import-btn').addEventListener('click', function() {
        hiddenUploadForm.hiddenFileInput.click();
    });

    //Bouton création de dossier
    document.getElementById('create-folder-btn').addEventListener('click', function () {
        const folderName = prompt(translations.new_folder_name);

        if (!folderName || folderName.trim() === "") {
            alert(translations.invalid_folder_name);
            return;
        }

        // Vérifier le dossier courant
        const filesListContainer = document.getElementById('files-list');
        const currentFolder = filesListContainer.getAttribute('krbe-filemanager-currentfolder') || '';

        fetch(krbeFilemanagerUrlFctCreateFolder, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ folderName: folderName.trim(), currentFolder: currentFolder })
        })
            .then(response => response.json())
            .then(data => {
                if (data.folderPath) {
                    alert(translations.folder_created);
                    loadFilesList(data.folderPath); // Recharger la liste des fichiers
                    loadTree(data.folderPath.slice(1)); // Recharger l'arborescence
                } else {
                    alert(translations.folder_create_error + (data.error || ""));
                }
            })
            .catch(error => {
                console.error("Erreur lors de la création du dossier :", error);
                alert(translations.folder_create_error_generic);
            });
    });

    // Gestion de CropperJS via le bouton "Crop"
    let cropper;
    const cropModal = document.getElementById('crop-modal');
    const cropImage = document.getElementById('crop-image');
    const widthInput = document.getElementById('crop-width');
    const heightInput = document.getElementById('crop-height');
    const applyDimensionsButton = document.getElementById('apply-dimensions');
    // Appliquer les dimensions indiquées par l'utilisateur
    applyDimensionsButton.addEventListener('click', function() {
        const newWidth = parseInt(widthInput.value);
        const newHeight = parseInt(heightInput.value);
        if (!isNaN(newWidth) && !isNaN(newHeight)) {
            cropper.setCropBoxData({ width: newWidth, height: newHeight });
        } else {
            alert(translations.invalid_dimensions);
        }
    });
    // Confirmer le crop
    document.getElementById('crop-confirm').addEventListener('click', function() {
        const croppedCanvas = cropper.getCroppedCanvas();
        croppedCanvas.toBlob(function(blob) {
            const formData = new FormData();
            formData.append('relativeFilePath', cropImage.dataset.relativePath);
            formData.append('x', cropper.getData().x);
            formData.append('y', cropper.getData().y);
            formData.append('width', cropper.getData().width);
            formData.append('height', cropper.getData().height);
            // Transmettre le sous-dossier si nécessaire
            formData.append('subFolder', "{{ currentFolder }}");
            fetch(krbeFilemanagerUrlFctCrop, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.relativeFilePath) {
                        alert(translations.image_cropped_successfully);
                        const filesListContainer = document.getElementById('files-list');
                        var folder = filesListContainer.getAttribute('krbe-filemanager-currentfolder');
                        loadFilesList(folder);
                        // Fermer la modale de crop
                        cropModal.style.display = 'none';
                        if(cropper) {
                            cropper.destroy();
                        }
                    } else {
                        alert(translations.error_during_cropping);
                    }
                });
        });
    });
    document.getElementById('crop-cancel').addEventListener('click', function() {
        cropModal.style.display = 'none';
        if(cropper) {
            cropper.destroy();
        }
    });

    // MOVE
    const moveModal = document.getElementById('move-modal');
    // MOVE: Attacher un événement aux liens de l'arborescence dans la modale
    document.querySelectorAll('.move-select').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            // Marquer le dossier sélectionné
            document.querySelectorAll('.move-directory-tree li').forEach(li => li.classList.remove('selected'));
            this.parentElement.classList.add('selected');
        });
    });
    // MOVE: Lorsque l'utilisateur confirme le déplacement
    document.getElementById('move-confirm').addEventListener('click', function() {
        console.log('move confirm');
        // Récupérer le chemin du dossier sélectionné
        const selectedLi = document.querySelector('.move-directory-tree li.selected');
        if (!selectedLi) {
            alert(translations.select_destination_folder);
            return;
        }
        const destinationSubFolder = selectedLi.getAttribute('data-path');
        // Envoyer la requête AJAX pour déplacer le fichier
        fetch(krbeFilemanagerUrlFctMove, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ relativePath: relativePath, destinationSubFolder: destinationSubFolder })
        })
            .then(response => response.json())
            .then(data => {
                if (data.newFilePath) {
                    alert(translations.move_successful);
                    const filesListContainer = document.getElementById('files-list');
                    var folder = filesListContainer.getAttribute('krbe-filemanager-currentfolder');
                    loadFilesList(folder);

                    moveModal.style.display = 'none';
                } else {
                    alert(translations.error_during_move + (data.error || ""));
                }
            })
            .catch(error => {
                console.error("Erreur:", error);
                alert(translations.error_during_move);
            });
    });
    // MOVE: Annulation
    document.getElementById('move-cancel').addEventListener('click', function() {
        moveModal.style.display = 'none';
    });

    // item action
    let relativePath = "";
    let publicPath = "";
    let currentName = "";
    document.getElementById('files-list').addEventListener('click', function(e) {
        // On récupère l'élément bouton le plus proche du clic
        const button = e.target.closest('button');
        if (!button) return; // Si ce n'est pas un bouton, on quitte

        const article = button.closest('article');
        relativePath = article.getAttribute('data-file');
        publicPath = article.getAttribute('data-public');
        currentName = article.getAttribute('data-name');

        if (button.classList.contains('select-btn')) {
            console.log("Selectionner cliqué pour :", relativePath);
            // Appeler le callback global pour transmettre le chemin sélectionné
            if (window.fileManagerSelectCallback) {
                window.fileManagerSelectCallback(relativePath);
            }
        } else if (button.classList.contains('rename-btn')) {
            console.log("Renommer cliqué pour :", relativePath);
            const newName = prompt(translations.enter_new_name, currentName);
            if (newName && newName !== currentName) {
                fetch(krbeFilemanagerUrlFctRename, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ relativePath: relativePath, newName: newName })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.newPath) {
                            alert(translations.renamed_successfully);
                            const filesListContainer = document.getElementById('files-list');
                            var folder = filesListContainer.getAttribute('krbe-filemanager-currentfolder');
                            loadFilesList(folder);
                        } else {
                            alert(translations.error_during_renaming + (data.error || ""));
                        }
                    })
                    .catch(error => {
                        console.error("Erreur :", error);
                        alert(translations.error_during_renaming);
                    });
            }
        } else if (button.classList.contains('delete-btn')) {
            console.log("Supprimer cliqué pour :", relativePath);
            if (confirm(translations.confirm_delete)) {
                fetch(krbeFilemanagerUrlFctDelete, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ relativePath: relativePath })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(translations.deleted_successfully);
                            const filesListContainer = document.getElementById('files-list');
                            var folder = filesListContainer.getAttribute('krbe-filemanager-currentfolder');
                            loadFilesList(folder);
                        } else {
                            alert(translations.error_during_deletion);
                        }
                    })
                    .catch(error => {
                        console.error("Erreur :", error);
                        alert(translations.error_during_deletion);
                    });
            }
        } else if (button.classList.contains('move-btn')) {
            console.log("Déplacer cliqué pour :", relativePath);
            // Code pour déplacer...

            // Ouvrir la modale de déplacement
            moveModal.style.display = 'flex';

            // Optionnel : vider une sélection précédente
            document.querySelectorAll('.move-directory-tree li').forEach(li => {
                li.classList.remove('selected');
            });

        } else if (button.classList.contains('crop-btn')) {
            console.log("Crop cliqué pour :", relativePath);
            cropImage.src = publicPath;
            cropImage.dataset.relativePath = relativePath;
            cropModal.style.display = 'flex';
            cropper = new Cropper(cropImage, { aspectRatio: NaN });
            // Initialiser les champs de dimensions avec la taille actuelle du crop box
            const cropBoxData = cropper.getCropBoxData();
            widthInput.value = Math.round(cropBoxData.width);
            heightInput.value = Math.round(cropBoxData.height);
        }
    });

    // Gérer la sélection de fichier en mode picker
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('select-btn')) {
            const fileItem = e.target.closest('.file-item');
            if (fileItem) {
                const filePath = fileItem.dataset.file;
                const publicPath = fileItem.dataset.public;
                
                // Envoyer un message au parent
                window.parent.postMessage({
                    type: 'filemanager:select',
                    filePath: filePath,
                    publicPath: publicPath
                }, '*');
            }
        }
    });

    // Gérer le double-clic sur les fichiers
    document.getElementById('files-list').addEventListener('dblclick', function(e) {
        const fileItem = e.target.closest('.file-item');
        if (fileItem) {
            const filePath = fileItem.dataset.file;
            const publicPath = fileItem.dataset.public;
            
            // Envoyer un message au parent
            window.parent.postMessage({
                type: 'filemanager:select',
                filePath: filePath,
                publicPath: publicPath
            }, '*');
        }
    });

    // Gestion du double tap
    let lastTap = 0;
    let lastTapTarget = null;

    document.addEventListener('touchend', function(e) {
        const currentTime = new Date().getTime();
        const tapLength = currentTime - lastTap;
        const fileItem = e.target.closest('.file-item');
        
        if (tapLength < 500 && tapLength > 0 && fileItem === lastTapTarget) {
            // Double tap détecté
            e.preventDefault();
            const filePath = fileItem.dataset.file;
            const publicPath = fileItem.dataset.public;
            
            // Envoyer un message au parent
            window.parent.postMessage({
                type: 'filemanager:select',
                filePath: filePath,
                publicPath: publicPath
            }, '*');
            
            // Réinitialiser le compteur
            lastTap = 0;
            lastTapTarget = null;
        } else {
            // Premier tap
            lastTap = currentTime;
            lastTapTarget = fileItem;
        }
    });

    // Gérer le tap sur le bouton de sélection
    document.addEventListener('touchend', function(e) {
        if (e.target && e.target.classList.contains('select-btn')) {
            e.preventDefault(); // Empêcher le double-tap
            const fileItem = e.target.closest('.file-item');
            if (fileItem) {
                const filePath = fileItem.dataset.file;
                const publicPath = fileItem.dataset.public;
                
                // Envoyer un message au parent
                window.parent.postMessage({
                    type: 'filemanager:select',
                    filePath: filePath,
                    publicPath: publicPath
                }, '*');
            }
        }
    });

});