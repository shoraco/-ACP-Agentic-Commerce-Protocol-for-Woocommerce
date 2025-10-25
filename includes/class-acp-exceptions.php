<?php
/**
 * ACP Custom Exceptions
 * Professional exception handling based on Magento ACP patterns
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base ACP exception
 */
class ACP_Exception extends Exception {
    public function __construct(string $message = "", int $code = 500, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * ACP validation exception
 */
class ACP_Validation_Exception extends ACP_Exception {
    public function __construct(string $message = "", int $code = 400, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * ACP authentication exception
 */
class ACP_Authentication_Exception extends ACP_Exception {
    public function __construct(string $message = "", int $code = 401, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * ACP authorization exception
 */
class ACP_Authorization_Exception extends ACP_Exception {
    public function __construct(string $message = "", int $code = 403, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * ACP not found exception
 */
class ACP_Not_Found_Exception extends ACP_Exception {
    public function __construct(string $message = "", int $code = 404, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * ACP conflict exception
 */
class ACP_Conflict_Exception extends ACP_Exception {
    public function __construct(string $message = "", int $code = 409, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * ACP rate limit exception
 */
class ACP_Rate_Limit_Exception extends ACP_Exception {
    public function __construct(string $message = "", int $code = 429, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * ACP server error exception
 */
class ACP_Server_Error_Exception extends ACP_Exception {
    public function __construct(string $message = "", int $code = 500, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
