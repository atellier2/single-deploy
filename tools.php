<?php

/**
 * Script qui permet de realiser différentes actions sur les environnements
 * 
 * suppression d'un répertoire
 *  delete_directory
 *      
 * 
 * 1. Supprimer un répertoire cible
 * 2. Le recréer en copiant un répertoire source.
 *
 * Exemple : 
 *	recreerrep.php?source=xxxx&environnement=yyyyy
 * 
 */

/**
 * PARAMETRAGES
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



$token_base = "f5d8e4c5-e8e5-4b2c-b8b0-c9f8f8d9b8b8"; // Token de sécurité

const DIRECTORY_RACINE_WEB = "/home/dtribcomdq/www";  //Sans le / final.


// Peut-on supprimer des répertoire qui ne sont pas des répertoires web ?
const ALLOW_DELETE_SUB_ROOT_DIR =  false;

const DEBUG_MODE = true;




/**
 * Suppression d'un répertoire et de son contenu
 */
function directory_delete($dir)
{
    Tools::logmsg("Début de suppression du répertoire $dir");

    //On vérifie que le répertoire existe
    Tools::OKOrExit(checkType::isDirectory, "repertoire à supprimer", $dir,);

    //On vérifie que le répertoire n'est pas un sous répertoire du répertoire racine
    Tools::OKOrExit(checkType::isNotSubDirectory, "repertoire à supprimer", $dir, DIRECTORY_RACINE_WEB);


    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                    directory_delete($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
        }
        rmdir($dir);
    }

    Tools::logmsg("Fin de suppression du répertoire $dir");
}

/**
 * Archivage du contenu d'un répertoire dans le répertoire d'archive
 */
function directory_archive($directory_path, $archive_path)
{
    //Vérification que le répertoire $directory_path existe
    Tools::OKOrExit(checkType::isDirectory, "repertoire à archiver", $directory_path,);

    //récupération du nom du répertoire à archiver
    $directory_name = basename($directory_path);

    //Vérification que le répertoire $archive_path existe
    Tools::OKOrExit(checkType::isDirectory, "repertoire d'archive", $archive_path,);

    $date = date("Y-m-d_H-i-s");
    $zip_archive_path = $archive_path . "/" . $directory_name . '.backup-' . $date . '.zip';
    Tools::logmsg("Sauvegarde dans le fichier $zip_archive_path");
    $cmd = "zip -r $zip_archive_path $directory_path";
    $compressFolder = exec($cmd . " 2>&1");
    if ($compressFolder) {
        Tools::logmsg("Le fichier $zip_archive_path a été créé");
        return true;
    } else {
        Tools::exitmsg("Le fichier $zip_archive_path n'a pas pu être créé");
        return false;
    }
}

/**
 * Fonction qui permet de dupliquer un répertoire source dans un répertoire destination
 * 
 * @param string $directory_name : Chemin complet du répertoire à duppliquer
 * @param string $destination_name : Chemin complet du répertoire dupliqué. Ce répertoire ne doit pas déjà exister
 */
function directory_duplicate($directory_name, $destination_name)
{ 
    Tools::logmsg("Début de la duplication du répertoire $directory_name vers $destination_name");

    //Vérification que le répertoire $directory_name existe
    //SI le premier caractère n'est pas un /, on ajoute le répertoire
    if (substr($directory_name, 0, 1) != DIRECTORY_SEPARATOR) {
        $directory_name = DIRECTORY_RACINE_WEB . DIRECTORY_SEPARATOR . $directory_name;
        logmsg("La racine a été rajoutée au répertoire à dupliquer : $directory_name");
    }
    Tools::OKOrExit(checkType::isDirectory, "directory_name", $directory_name);

    //Vérification que le répertoire $destination_name existe
    //SI le premier caractère n'est pas un /, on ajoute le répertoire
    if (substr($destination_name, 0, 1) != DIRECTORY_SEPARATOR) {
        $destination_name = DIRECTORY_RACINE_WEB . DIRECTORY_SEPARATOR . $destination_name;
    }
    Tools::OKOrExit(checkType::isNotEmpty, "destination_name", $destination_name);

    //On vérifie que $destination_name n'existe pas
    Tools::OKOrExit(checkType::isNotEmpty, "destination_name", $destination_name);

    //On vérifie que $directory_name n'est pas le répertoire de destination
    if ($directory_name == $destination_name) {
        Tools::exitmsg("Le répertoire $directory_name est le même que le répertoire de destination");
    }

    //On vérifie que $destination_name n'est pas un sous répertoire de $directory_name
    Tools::OKOrExit(checkType::isNotSubDirectory, "directory_name", $directory_name, "destination_name", $destination_name);

    Tools::logmsg("Création du répertoire de destination {$destination_name}");
    mkdir($destination_name, 0755); 
    Tools::OKOrExit(checkType::isDirectory, "destination_name", $destination_name);

    foreach ($iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($directory_name, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
    ) as $item) {
        if ($item->isDir()) {
            mkdir($destination_name . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
        } else {
            copy($item, $destination_name . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
        }
    }

    Tools::logmsg("Fin de la duplication du répertoire $directory_name vers $destination_name");
}

abstract class checkType
{
    const isDirectory = 0;
    const isNotDirectory = 1;
    const isNotSubDirectory = 4;
    const isEmpty = 2;
    const isNotEmpty = 3;
    const isBooleen = 5;
    // etc.
}

class Tools
{
    static function OKOrExit($checkType, $paramName, $value, $paramName2 = "", $value2 = "")
    {
        Tools::check($checkType, true, $paramName, $value, $paramName2, $value2);
    }

    static function check($checkType, $exitOnFalse, $paramName, $value, $paramName2 = "", $value2 = "")
    {
        switch ($checkType) {
            case checkType::isDirectory:
                Tools::check(checkType::isNotEmpty, $exitOnFalse, $paramName, $value);
                if (!is_dir($value)) {
                    $message = "Le répertoire $paramName {$value} n'existe pas";
                } else {
                    return true;
                }
                break;
            case checkType::isNotEmpty:
                if (empty($value)) {
                    $message = "Le paramètre $paramName est vide";
                } else  {
                    return true;
                }
                break;
            case checkType::isNotDirectory:
                Tools::check(checkType::isNotEmpty, $exitOnFalse, $paramName, $value);
                if (is_dir($value)) {
                    $message = "Le répertoire $paramName existe déjà";
                } else {
                    return true;
                }
                break;
            case checkType::isNotSubDirectory:
                Tools::check(checkType::isNotEmpty, $exitOnFalse, $paramName, $value);
                Tools::check(checkType::isNotEmpty, $exitOnFalse, $paramName2, $value2);
                if ((strpos($value2 . '/', $value . '/') !== false) || (strpos($value . '/', $value2 . '/') !== false)) {
                    $message = "Le répertoire $paramName est un sous répertoire du répertoire $paramName2";
                } else {
                    return true;
                }
                break;
            case checkType::isBooleen:
                if (!is_bool($value)) {
                    $message = "Le paramètre $paramName n'est pas un booléen";
                } else {
                    return true;
                }
                break;
            default:
                $message = "Le paramètre checkType $checkType n'est pas reconnu";
                break;
        }

        if ($exitOnFalse) {
            Tools::exitmsg($message);
        } else {
            Tools::logmsg($message);
        }
    }

    static function exitmsg($message)
    {
        Tools::logmsg($message, true);
    }

    static function logmsg($message, $exit = false)
    {
        $date = date("Y-M-D H:i:s");
        echo "<p>[$date] $message</p>";
        if ($exit)
            exit;
    }


    static function get($param_name, $exit_if_empty = true)
    {
        Tools::OKOrExit(checkType::isBooleen, "exit_if_empty", $exit_if_empty);

        Tools::logmsg("Récupération du paramètre $param_name");
        if (isset($_GET[$param_name])) {
            return $_GET[$param_name];
        } else {
            if ($exit_if_empty) {
                Tools::logmsg("Paramètre $param_name absent");
                exit();
            } else {
                return "";
            }
        }
    }
}


//Si le tocken n'est pas renseigné, on quitte l'application
$token = Tools::get("token");
if ($token != $token_base) {
    Tools::exitmsg("Le token est incorrect");
}

echo "<p>Pour info ce script se trouve dans le répertoire " . __DIR__ . "</p>";

$action = Tools::get("action");



switch ($action) {
    case 'directory_delete':
        Tools::get("directory_name");
        directory_delete($directory_name);
        break;
    case 'directory_archive':
        Tools::get("directory_name");
        Tools::get("archive_name");
        directory_archive($directory_name, $archive_name);
        break;
    case 'directory_duplicate':
        directory_duplicate(Tools::get('directory_name'), Tools::get('destination_name'));
        break;
    default:
        Tools::logmsg("Action inconnue");
        break;
}
