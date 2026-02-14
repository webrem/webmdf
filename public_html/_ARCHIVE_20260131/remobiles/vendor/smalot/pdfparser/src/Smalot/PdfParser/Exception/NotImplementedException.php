<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/runtime_page_tracker.php';


declare(strict_types=1);

namespace Smalot\PdfParser\Exception;

/**
 * This Exception is thrown when a functionality has not yet been implemented.
 */
class NotImplementedException extends \Exception
{
}
