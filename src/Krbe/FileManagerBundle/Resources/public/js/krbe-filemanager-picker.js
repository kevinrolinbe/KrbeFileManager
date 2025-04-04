document.addEventListener("DOMContentLoaded", function () {
    // Configuration par défaut
    const defaultConfig = {
        wrapperClass: 'krbe-filemanager-wrapper',
        inputAttribute: 'data-krbe-filemanager',
        inputText: 'Choisir un fichier',
        inputClass: 'krbe-filemanager-input',
        buttonSelectText: 'Choisir un fichier',
        buttonSelectClass: 'krbe-filemanager-select-btn',
        buttonResetText: 'Réinitialiser',
        buttonResetClass: 'krbe-filemanager-reset-btn',
        previewClass: 'krbe-filemanager-preview',
    };

    // Appliquer la configuration personnalisée si elle existe
    if (window.KrbeFileManagerConfig) {
        Object.assign(defaultConfig, window.KrbeFileManagerConfig);
    }

    // Fonction pour surcharger la configuration
    window.KrbeFileManager = {
        setConfig: function(customConfig) {
            Object.assign(defaultConfig, customConfig);
            // Réinitialiser tous les inputs avec la nouvelle configuration
            initAllFileManagerInputs();
        }
    };

    // Fonction pour générer un ID aléatoire
    function generateRandomId() {
        const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let id = 'krbe-filemanager-';
        for (let i = 0; i < 10; i++) {
            id += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return id;
    }

    // Fonction pour créer le wrapper et le bouton pour un input
    function createFileManagerWrapper(input) {
        // Vérifier si l'input est déjà initialisé
        if (input.dataset.filemanagerInitialized === "true") {
            return;
        }
        input.dataset.filemanagerInitialized = "true";

        // Générer un ID si l'input n'en a pas
        if (!input.id) {
            input.id = generateRandomId();
        }

        // Rendre l'input readonly
        input.readOnly = true;

        // Créer le wrapper
        const wrapper = document.createElement('div');
        wrapper.className = defaultConfig.wrapperClass;

        // Créer le conteneur pour l'aperçu de l'image si c'est un input pour images
        let previewContainer = null;
        if (input.getAttribute(defaultConfig.inputAttribute) === 'img') {
            previewContainer = document.createElement('div');
            previewContainer.className = defaultConfig.previewClass;
            wrapper.appendChild(previewContainer);
        }

        // Vérifier si l'input a déjà une valeur et mettre à jour la prévisualisation
        if (input.value && previewContainer) {
            previewContainer.innerHTML = `<img src="${input.value}" alt="Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
        }

        // Créer le bouton de sélection
        const selectButton = document.createElement('button');
        selectButton.type = 'button';
        selectButton.textContent = defaultConfig.buttonSelectText;
        selectButton.className = defaultConfig.buttonSelectClass;
        selectButton.setAttribute('role', 'button');
        selectButton.setAttribute('tabindex', '0');

        // Créer le bouton de réinitialisation
        const resetButton = document.createElement('button');
        resetButton.type = 'button';
        resetButton.textContent = defaultConfig.buttonResetText;
        resetButton.className = defaultConfig.buttonResetClass;
        resetButton.setAttribute('role', 'button');
        resetButton.setAttribute('tabindex', '0');

        // Ajouter la classe à l'input
        input.classList.add(defaultConfig.inputClass);
        input.setAttribute('tabindex', '0'); // Permettre le focus sur l'input
        input.setAttribute('placeholder', defaultConfig.inputText); // Ajouter le placeholder

        // Fonction pour ouvrir la modale
        function openModal() {
            const modal = document.getElementById('filemanager-modal');
            if (modal) {
                modal.style.display = 'block';
                modal.dataset.targetInput = input.id;
                modal.dataset.isImage = input.getAttribute(defaultConfig.inputAttribute) === 'img';
            }
        }

        // Fonction pour gérer les événements tactiles et clic
        function handleInteraction(e) {
            e.preventDefault(); // Empêcher le comportement par défaut
            openModal();
        }

        // Fonction pour réinitialiser l'input
        function resetInput() {
            input.value = '';
            const previewContainer = wrapper.querySelector(`.${defaultConfig.previewClass}`);
            if (previewContainer) {
                previewContainer.innerHTML = '';
            }
            resetButton.style.display = 'none';
            
            // Émettre un événement personnalisé lors de la réinitialisation
            const resetEvent = new CustomEvent('krbeFileManager:reset', {
                detail: { input }
            });
            input.dispatchEvent(resetEvent);
        }

        // Fonction pour mettre à jour l'affichage du bouton de réinitialisation
        function updateResetButtonVisibility() {
            resetButton.style.display = input.value ? 'block' : 'none';
        }

        // Ajouter les événements tactiles et clic
        selectButton.addEventListener('click', handleInteraction);
        selectButton.addEventListener('touchend', handleInteraction);
        resetButton.addEventListener('click', resetInput);
        resetButton.addEventListener('touchend', function(e) {
            e.preventDefault();
            resetInput();
        });

        // Ajouter les événements sur l'input
        input.addEventListener('click', handleInteraction);
        input.addEventListener('touchend', handleInteraction);

        // Observer les changements de valeur de l'input
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                    updateResetButtonVisibility();
                }
            });
        });
        observer.observe(input, { attributes: true });

        // Vérifier la valeur initiale
        updateResetButtonVisibility();

        // Insérer le wrapper avant l'input
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        wrapper.appendChild(resetButton);
        wrapper.appendChild(selectButton);
    }

    // Fonction pour initialiser tous les inputs de file manager sur la page
    function initAllFileManagerInputs() {
        const inputs = document.querySelectorAll(`input[${defaultConfig.inputAttribute}]`);
        inputs.forEach(input => createFileManagerWrapper(input));
    }

    // Gérer le bouton de fermeture de la modale
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('filemanager-modal-close')) {
            const modal = document.getElementById('filemanager-modal');
            if (modal) {
                modal.style.display = 'none';
                delete modal.dataset.targetInput;
                delete modal.dataset.isImage;
            }
        }
    });

    // Ajouter la gestion des événements tactiles sur le bouton de fermeture
    document.addEventListener('touchend', function(e) {
        if (e.target && e.target.classList.contains('filemanager-modal-close')) {
            e.preventDefault();
            const modal = document.getElementById('filemanager-modal');
            if (modal) {
                modal.style.display = 'none';
                delete modal.dataset.targetInput;
                delete modal.dataset.isImage;
            }
        }
    });

    // Écouter les messages du file manager
    window.addEventListener('message', function(event) {
        console.log('Message reçu:', event.data); // Debug log
        
        // Vérifier l'origine du message pour la sécurité
        if (!event.origin) {
            console.warn('Message sans origine reçu');
            return;
        }

        if (event.data && event.data.type === 'filemanager:select') {
            console.log('Sélection de fichier détectée:', event.data); // Debug log
            
            const modal = document.getElementById('filemanager-modal');
            if (modal) {
                const targetInputId = modal.dataset.targetInput;
                const input = document.getElementById(targetInputId);
                
                if (input) {
                    console.log('Input trouvé:', input.id); // Debug log
                    
                    // Utiliser le chemin public complet du fichier
                    input.value = event.data.publicPath;
                    
                    // Mettre à jour la prévisualisation si c'est une image
                    if (modal.dataset.isImage === 'true') {
                        const wrapper = input.closest(`.${defaultConfig.wrapperClass}`);
                        if (wrapper) {
                            const previewContainer = wrapper.querySelector(`.${defaultConfig.previewClass}`);
                            if (previewContainer) {
                                previewContainer.innerHTML = `<img src="${event.data.publicPath}" alt="Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
                            }
                        }
                    }
                    
                    // Afficher le bouton de réinitialisation
                    const resetButton = input.parentElement.querySelector(`.${defaultConfig.buttonResetClass}`);
                    if (resetButton) {
                        resetButton.style.display = 'block';
                    }
                    
                    // Émettre un événement personnalisé lors de la sélection
                    const selectEvent = new CustomEvent('krbeFileManager:selected', {
                        detail: { input, selectedFilePath: event.data.publicPath }
                    });
                    input.dispatchEvent(selectEvent);
                } else {
                    console.error('Input non trouvé:', targetInputId); // Debug log
                }
                modal.style.display = 'none';
                delete modal.dataset.targetInput;
                delete modal.dataset.isImage;
            } else {
                console.error('Modal non trouvée'); // Debug log
            }
        }
    });

    // Initialisation par défaut
    initAllFileManagerInputs();

    // Observer pour détecter les ajouts dynamiques d'inputs
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.matches(`input[${defaultConfig.inputAttribute}]`)) {
                        createFileManagerWrapper(node);
                    }
                    node.querySelectorAll && node.querySelectorAll(`input[${defaultConfig.inputAttribute}]`).forEach(createFileManagerWrapper);
                }
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
});