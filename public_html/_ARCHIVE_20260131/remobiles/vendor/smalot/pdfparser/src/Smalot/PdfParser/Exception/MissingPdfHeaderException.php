<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';


declare(strict_types=1);

namespace Smalot\PdfParser\Exception;

/**
 * This Exception is thrown when the %PDF- header is missing.
 */
class MissingPdfHeaderException extends \Exception
{
}
