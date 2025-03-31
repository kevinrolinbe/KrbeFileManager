<?php

namespace Krbe\FileManagerBundle\Exception;

class FileManagerException extends \Exception
{
    public const ERROR_INVALID_PATH = 1;
    public const ERROR_INVALID_MIME_TYPE = 2;
    public const ERROR_FILE_TOO_LARGE = 3;
    public const ERROR_UPLOAD_FAILED = 4;
    public const ERROR_DELETE_FAILED = 5;
    public const ERROR_MOVE_FAILED = 6;
    public const ERROR_RENAME_FAILED = 7;
    public const ERROR_PERMISSION_DENIED = 8;
    public const ERROR_FILE_NOT_FOUND = 9;
    public const ERROR_INVALID_OPERATION = 10;
    public const ERROR_QUOTA_EXCEEDED = 11;

    private static array $errorMessages = [
        self::ERROR_INVALID_PATH => 'Le chemin du fichier est invalide. Vérifiez qu\'il ne contient pas de caractères spéciaux ou de tentatives de navigation.',
        self::ERROR_INVALID_MIME_TYPE => 'Le type de fichier n\'est pas autorisé. Types autorisés : %s',
        self::ERROR_FILE_TOO_LARGE => 'Le fichier est trop volumineux. Taille maximale autorisée : %s',
        self::ERROR_UPLOAD_FAILED => 'L\'upload du fichier a échoué : %s',
        self::ERROR_DELETE_FAILED => 'La suppression du fichier a échoué : %s',
        self::ERROR_MOVE_FAILED => 'Le déplacement du fichier a échoué : %s',
        self::ERROR_RENAME_FAILED => 'Le renommage du fichier a échoué : %s',
        self::ERROR_PERMISSION_DENIED => 'Permission refusée : %s',
        self::ERROR_FILE_NOT_FOUND => 'Fichier non trouvé : %s',
        self::ERROR_INVALID_OPERATION => 'Opération invalide : %s',
        self::ERROR_QUOTA_EXCEEDED => 'Quota de stockage dépassé. Quota maximum : %s'
    ];

    public static function createFromCode(int $code, string ...$params): self
    {
        $message = self::$errorMessages[$code] ?? 'Une erreur inconnue est survenue';
        if (!empty($params)) {
            $message = sprintf($message, ...$params);
        }
        return new self($message, $code);
    }
} 