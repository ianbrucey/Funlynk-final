<?php

namespace App\Poster\Exceptions;

use RuntimeException;

/**
 * Thrown by platform adapters when the credentials / account / client
 * implementation needed to publish do not exist yet. Phase 1 adapters
 * always throw this from publish() — there are no fake success paths.
 */
class NotConfiguredException extends RuntimeException {}
