<?php

namespace App\Poster\Exceptions;

use RuntimeException;

/**
 * Thrown when anything attempts to publish a bundle that is not in the
 * `approved` state. This is the enforcement point for the approval rule.
 */
class PostingNotAllowedException extends RuntimeException {}
