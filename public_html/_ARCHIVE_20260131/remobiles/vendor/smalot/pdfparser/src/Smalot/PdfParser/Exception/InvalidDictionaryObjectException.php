<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';


declare(strict_types=1);

namespace Smalot\PdfParser\Exception;

/**
 * This exception is thrown when an invalid dictionary object is encountered.
 */
class InvalidDictionaryObjectException extends \Exception
{
}
