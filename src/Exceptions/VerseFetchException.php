<?php

namespace PkDev\VerseClient\Exceptions;

use RuntimeException;

/**
 * Thrown when a verse/passage cannot be retrieved (transport error, auth
 * failure, unknown reference, or empty payload). New fetch methods throw this
 * instead of returning sentinel strings, so callers can distinguish a real
 * passage from a failure without string matching.
 */
class VerseFetchException extends RuntimeException {}
