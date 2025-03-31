<?php

namespace Krbe\FileManagerBundle\Controller;

use Krbe\FileManagerBundle\Helper\FileNameHelper;
use Krbe\FileManagerBundle\Resolver\UploadPathResolverInterface;
use Krbe\FileManagerBundle\Service\FileManagerServiceInterface;
use Krbe\FileManagerBundle\Service\ImageProcessingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Krbe\FileManagerBundle\Exception\FileManagerException;

/**
* Classe FileManagerController
* Contrôleur pour gérer les opérations du gestionnaire de fichiers.
*/
#[Route(path: '/filemanager', priority: 10)]
class FileManagerController extends AbstractController
{

    /**
    * Constructeur de FileManagerController.
    *
    * @param FileManagerServiceInterface $fileManagerService Le service de gestion des fichiers.
    * @param ImageProcessingService $imageProcessingService Le service de traitement des images.
    * @param UploadPathResolverInterface $uploadPathResolver Le resolver pour obtenir le chemin d'upload.
    * @param TranslatorInterface $translator Le traducteur pour les messages de traduction.
    */
    public function __construct(
        private FileManagerServiceInterface $fileManagerService,
        private ImageProcessingService $imageProcessingService,
        private UploadPathResolverInterface $uploadPathResolver,
        private FileNameHelper $fileNameHelper,
        private TranslatorInterface $translator
    ) {
    }

     #[Route('/delete', name: 'file_manager_delete', methods: ['POST'])]
     public function delete(Request $request): JsonResponse
     {
         $this->denyAccessUnlessGranted('ROLE_FILE_WRITE');
         $data = json_decode($request->getContent(), true);
         $relativePath = $data['relativePath'] ?? '';
         
         $success = $this->fileManagerService->deleteFile($relativePath);
         return new JsonResponse([
             'success' => $success,
             'message' => $success ? $this->translator->trans('krbe_file_manager.ui.success.deleted') : $this->translator->trans('krbe_file_manager.ui.error.delete')
         ], $success ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
     }

     #[Route('/rename', name: 'file_manager_rename', methods: ['POST'])]
     public function rename(Request $request): JsonResponse
     {
         $this->denyAccessUnlessGranted('ROLE_FILE_WRITE');

         try {
             $data = json_decode($request->getContent(), true);
             if (!isset($data['relativePath']) || !isset($data['newName'])) {
                 return new JsonResponse(['error' => $this->translator->trans('krbe_file_manager.errors.empty_filename')], Response::HTTP_BAD_REQUEST);
             }

             $newFilePath = $this->fileManagerService->renameFile(
                 $data['relativePath'],
                 $data['newName']
             );

             return new JsonResponse(['newPath' => $newFilePath]);
         } catch (\Exception $e) {
             return new JsonResponse(['error' => $this->translator->trans('krbe_file_manager.ui.error.rename')], Response::HTTP_BAD_REQUEST);
         }
     }

     #[Route(path: '/move', name: 'file_manager_move', methods: ['POST'])]
     public function move(Request $request): JsonResponse
     {
         $this->denyAccessUnlessGranted('ROLE_FILE_WRITE');

         $data = json_decode($request->getContent(), true);
         $relativePath = $data['relativePath'] ?? '';
         $destinationSubFolder = $data['destinationSubFolder'] ?? '';

         try {
             // On passe le chemin relatif et le sous-dossier de destination à la méthode moveFile du FileManagerService
             $newRelativePath = $this->fileManagerService->moveFile($relativePath, $destinationSubFolder);
             return new JsonResponse(['newFilePath' => $newRelativePath], Response::HTTP_OK);
         } catch (\Exception $e) {
             return new JsonResponse(['error' => $this->translator->trans('krbe_file_manager.ui.error.move', ['error' => $e->getMessage()])], Response::HTTP_INTERNAL_SERVER_ERROR);
         }
     }

    #[Route('/create-folder', name: 'file_manager_create_folder', methods: ['POST'])]
    public function createFolder(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_FILE_WRITE');

        $data = json_decode($request->getContent(), true);
        $folderName = $data['folderName'] ?? null;
        $currentFolder = $data['currentFolder'] ?? '';

        if (!$folderName || preg_match('/[^A-Za-z0-9_\-]/', $folderName)) {
            return new JsonResponse(['error' => $this->translator->trans('krbe_file_manager.errors.invalid_filename')], Response::HTTP_BAD_REQUEST);
        }

        // Construire le chemin complet du nouveau dossier
        $relativePath = '/' . trim($currentFolder, '/') . '/' . $folderName;

        try {
            // On passe le chemin relatif et le sous-dossier de destination à la méthode moveFile du FileManagerService
            $folderPath = $this->fileManagerService->createFolder($relativePath);
            return new JsonResponse(['folderPath' => $relativePath], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $this->translator->trans('krbe_file_manager.ui.error.folder_create', ['error' => $e->getMessage()])], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

     /**
      * Téléversement d'un fichier.
      *
      * @param Request $request La requête HTTP.
      * @return JsonResponse La réponse JSON.
      */
     #[Route(path: '/upload', name: 'file_manager_upload', methods: ['POST'])]
     public function upload(Request $request): JsonResponse
     {
         $this->denyAccessUnlessGranted('ROLE_FILE_WRITE');

         $subFolder = $request->request->get('subFolder', '');
         $file = $request->files->get('file');

         if (!$file) {
             return new JsonResponse([
                 'error' => $this->translator->trans('krbe_file_manager.ui.error.invalid_file')
             ], Response::HTTP_BAD_REQUEST);
         }

         if (!$file->isValid()) {
            return new JsonResponse([
                 'error' => $this->translator->trans('krbe_file_manager.ui.error.invalid_file')
             ], Response::HTTP_BAD_REQUEST);
         }

         try {
             $filePath = $this->fileManagerService->uploadFile($file, $subFolder);
             return new JsonResponse([
                 'success' => true,
                 'message' => $this->translator->trans('krbe_file_manager.ui.success.file_uploaded'),
                 'filePath' => $filePath
             ], Response::HTTP_OK);
         } catch (FileManagerException $e) {
             return new JsonResponse([
                 'error' => $e->getMessage()
             ], Response::HTTP_INTERNAL_SERVER_ERROR);
         }
     }

     /**
      * Edition (crop) d'une image existante.
      *
      * @param Request $request La requête HTTP.
      * @return JsonResponse La réponse JSON.
      */
    #[Route(path: '/crop', name: 'file_manager_crop', methods: ['POST'])]
    public function crop(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_FILE_WRITE');

        // Récupération des paramètres de crop
        $relativeFilePath = $request->request->get('relativeFilePath');
        $x = (int)$request->request->get('x');
        $y = (int)$request->request->get('y');
        $width = (int)$request->request->get('width');
        $height = (int)$request->request->get('height');

        if (!$relativeFilePath) {
            return new JsonResponse(['error' => $this->translator->trans('krbe_file_manager.errors.invalid_operation')], Response::HTTP_BAD_REQUEST);
        }

        // Obtenir le chemin absolu de base depuis le resolver
        $storagePath = $this->uploadPathResolver->getUploadPath();

        // Construire le chemin complet du fichier source
        $fullSourcePath = $storagePath . '/' . ltrim($relativeFilePath, '/');
        if (!file_exists($fullSourcePath)) {
            return new JsonResponse(
                [
                    'error' => $this->translator->trans('krbe_file_manager.errors.file_not_found'),
                    'fullSourcePath' => $fullSourcePath
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Utiliser le dossier source pour créer le fichier cropé
        $outputDir = dirname($fullSourcePath);
        $originalName = pathinfo($fullSourcePath, PATHINFO_FILENAME);
        $extension = pathinfo($fullSourcePath, PATHINFO_EXTENSION);

        // Générer un nom unique pour le fichier cropé (sans écraser d'autres crops)
        $finalName = $this->fileNameHelper->getUniqueFileNameInFolder($outputDir, $originalName . '_cropped.' . $extension);
        $fullOutputPath = $outputDir . '/' . $finalName;

        $fullSourcePath = str_replace($storagePath, '', $fullSourcePath);
        $fullOutputPath = str_replace($storagePath, '', $fullOutputPath);

        // Appliquer le crop en passant les chemins complets
        $result = $this->imageProcessingService->cropImage($fullSourcePath, $x, $y, $width, $height, $fullOutputPath);

        if ($result) {
            // Pour renvoyer un chemin relatif, on retire le préfixe $storagePath du chemin de sortie
            $relativeOutputPath = str_replace($storagePath . '/', '', $fullOutputPath);
            return new JsonResponse(['relativeFilePath' => $relativeOutputPath], Response::HTTP_OK);
        } else {
            return new JsonResponse(['error' => $this->translator->trans('krbe_file_manager.ui.error.crop')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Action pour afficher le file manager en mode widget.
     * Vous pouvez passer un paramètre "currentFolder" en GET pour définir le dossier affiché par défaut.
     */
    #[Route('/widget', name: 'file_manager_widget')]
    public function widget(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_FILE_READ');

        // Vous pouvez définir ici des valeurs par défaut
        $currentFolder = $request->query->get('currentFolder', '');

        // Utiliser le service pour obtenir l'arborescence et les fichiers
        $directoryTree = $this->fileManagerService->listDirectoryTree($currentFolder);
        $files = $this->fileManagerService->listFiles($currentFolder);

        return $this->render('@KrbeFileManager/partials/widget.html.twig', [
            'directoryTree' => $directoryTree,
            'files' => $files,
            'currentFolder' => $currentFolder,
        ]);
    }
    #[Route('/widget/listfolders', name: 'file_manager_widget_listfolders')]
    public function widgetListfolders(Request $request, ?string $subFolder = ''): Response
    {
        $this->denyAccessUnlessGranted('ROLE_FILE_READ');

        // Vous pouvez définir ici des valeurs par défaut
        $currentFolder = $request->query->get('currentFolder', '');

        // Utiliser le service pour obtenir l'arborescence et les fichiers
        $directoryTree = $this->fileManagerService->listDirectoryTree($currentFolder);
        $files = $this->fileManagerService->listFiles($subFolder);

        // Filtrer pour ne garder que les dossiers
        $folders = array_filter($files, function($item) {
            return $item['isDirectory'];
        });

        return $this->render('@KrbeFileManager/partials/list_folders.html.twig', [
            'directoryTree' => $directoryTree,
            'folders' => $folders,
        ]);
    }
    #[Route('/widget/listfiles/{subFolder?}', name: 'file_manager_widget_listfiles', requirements: ['subFolder' => '.*'])]
    public function widgetListfiles(Request $request, ?string $subFolder = ''): Response
    {
        $this->denyAccessUnlessGranted('ROLE_FILE_READ');

        $files = $this->fileManagerService->listFiles($subFolder);

        $parent = null;
        if( $subFolder != '' ){
            $parent = [
                'relativePath' => $this->getParentPath($subFolder),
                'name' => $this->getParentFolderName($subFolder),
            ];
        }

        return $this->render('@KrbeFileManager/partials/list_files.html.twig', [
            'files' => $files,
            'parent' => $parent,
        ]);
    }

    #[Route('/widgetmodal', name: 'file_manager_widget_modal')]
    public function widgetModal(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_FILE_READ');

        $currentFolder = $request->query->get('currentFolder', '');

        return $this->render('@KrbeFileManager/file_manager_modal.html.twig', [
            'currentFolder' => $currentFolder,
        ]);
    }

    /**
      * Affichage principal du file manager.
      *
      * @param Request $request La requête HTTP.
      * @param string|null $subFolder Le sous-dossier courant.
      * @return Response La réponse HTTP.
      */
     #[Route('/', name: 'file_manager_index')]
     public function index(Request $request, ?string $subFolder = ''): Response
     {
         $this->denyAccessUnlessGranted('ROLE_FILE_READ');

         $currentFolder = $request->query->get('currentFolder', '');

         $directoryTree = $this->fileManagerService->listDirectoryTree();
         $files = $this->fileManagerService->listFiles($currentFolder);

         return $this->render('@KrbeFileManager/file_manager.html.twig', [
             'directoryTree' => $directoryTree,
             'files'         => $files,
             'currentFolder' => $currentFolder,
         ]);
     }

    function getParentPath(string $relativePath): string {
        $parts = explode('/', rtrim($relativePath, '/'));
        array_pop($parts);
        return implode('/', $parts);
    }
    function getParentFolderName(string $relativePath): string {
        // Supprimez les slashs de fin
        $relativePath = rtrim($relativePath, '/');
        // Si le chemin ne contient pas de slash, il n'y a pas de parent
        if (false === $pos = strrpos($relativePath, '/')) {
            return 'Root';
        }
        // Extraire le parent (tout avant le dernier slash)
        $parentPath = substr($relativePath, 0, $pos);
        // Retourner le nom de base du chemin parent
        return basename($parentPath);
    }

}