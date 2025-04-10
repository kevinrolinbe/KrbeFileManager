(function() {
    tinymce.PluginManager.add('krbefilemanager', function(editor, url) {
        // Récupération du chemin configuré ou utilisation de la valeur par défaut
        const fileManagerPath = editor.getParam('fileManagerPath', '/krbe/filemanager');
        
        // Fonction pour détecter si un fichier est une image
        function isImageFile(url) {
            const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'];
            return imageExtensions.some(ext => url.toLowerCase().endsWith(ext));
        }

        // Fonction pour ouvrir le filemanager
        function openFileManager(options) {
            editor.windowManager.openUrl({
                title: 'File Manager',
                url: fileManagerPath + '/tinymce',
                width: 900,
                height: 600,
                buttons: [
                    {
                        type: 'cancel',
                        text: 'Fermer'
                    }
                ],
                onMessage: function(api, details) {
                    // On accepte tous les messages qui contiennent une URL
                    if (details.content) {
                        const fileUrl = details.content;
                        const fileName = details.text || fileUrl.split('/').pop();

                        // Si on est en mode fichier, on insère toujours un lien
                        if (options.type === 'file') {
                            editor.insertContent('<a href="' + fileUrl + '">' + fileName + '</a>');
                        }
                        // Sinon, on gère selon le type de fichier
                        else if (isImageFile(fileUrl)) {
                            if (options.callback) {
                                options.callback(fileUrl);
                            } else {
                                editor.insertContent('<img src="' + fileUrl + '" />');
                            }
                        } else {
                            editor.insertContent('<a href="' + fileUrl + '">' + fileName + '</a>');
                        }
                        api.close();
                    }
                }
            });
        }

        // Ajouter le bouton pour insérer des fichiers dans la barre d'outils
        editor.ui.registry.addButton('krbefilemanager', {
            icon: 'link',
            tooltip: 'Insérer un fichier',
            onAction: function() {
                openFileManager({
                    type: 'file'
                });
            }
        });

        // Ajouter le bouton pour insérer des images dans la barre d'outils
        editor.ui.registry.addButton('krbeimagemanger', {
            icon: 'image',
            tooltip: 'Insérer une image',
            onAction: function() {
                openFileManager({
                    type: 'image'
                });
            }
        });

        // Configurer le file_picker_callback pour la boîte de dialogue d'image
        editor.settings.file_picker_callback = function(callback, value, meta) {
            if (meta.filetype === 'image') {
                openFileManager({
                    type: 'image',
                    callback: callback
                });
            }
        };

        return {
            getMetadata: function() {
                return {
                    name: 'Krbe File Manager',
                    url: 'https://kevinrolin.be',
                    version: '1.0'
                };
            }
        };
    });
})(); 