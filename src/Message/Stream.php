<?php

namespace Lemon\Http\Message;

use Psr\Http\Message\StreamInterface;

/**
 * Represents a data stream as defined in PSR-7.
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php
 */
class Stream implements StreamInterface
{
    /**
     * The underlying stream resource
     *
     * @var resource
     */
    protected $stream;

    /**
     * Stream metadata
     *
     * @var array
     */
    protected $meta;

    /**
     * Is this stream readable?
     *
     * @var bool
     */
    protected $readable;

    /**
     * Is this stream writable?
     *
     * @var bool
     */
    protected $writable;

    /**
     * Is this stream seekable?
     *
     * @var bool
     */
    protected $seekable;

    /**
     * The size of the stream if known
     *
     * @var null|int
     */
    protected $size;

    /**
     * Create a new Stream.
     *
     * @param resource $resource A PHP resource handle.
     *
     * @throws \InvalidArgumentException If argument is not a resource.
     */
    public function __construct($resource)
    {
        $this->attach($resource);
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        if ($this->isReadable()) {
            try {
                $this->rewind();

                return $this->getContents();
            } catch (\RuntimeException $e) {
            }
        }

        return '';
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        $stream = $this->detach();
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * Is a resource attached to this stream?
     *
     * NOTE: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    protected function isAttached()
    {
        return is_resource($this->stream);
    }

    /**
     * Attach new resource to this object.
     *
     * NOTE: This method is not part of the PSR-7 standard.
     *
     * @param resource $stream A PHP resource handle.
     * @throws \InvalidArgumentException If argument is not a valid PHP resource.
     */
    protected function attach($stream)
    {
        if (is_resource($stream) === false) {
            throw new \InvalidArgumentException(__METHOD__ . ' argument must be a valid PHP resource');
        }

        if ($this->isAttached()) {
            $this->detach();
        }

        $this->stream = $stream;
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $oldStream = $this->stream;
        unset($this->stream, $this->meta, $this->readable, $this->writable, $this->seekable, $this->size);

        return $oldStream;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (is_null($this->size) && $this->isAttached()) {
            $stats = fstat($this->stream);
            $this->size = isset($stats['size']) ? $stats['size'] : null;
        }

        return $this->size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if (!$this->isAttached() || ($position = ftell($this->stream)) === false) {
            throw new \RuntimeException('Could not get the position of the pointer in stream');
        }

        return $position;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return $this->isAttached() ? feof($this->stream) : true;
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        if (is_null($this->seekable)) {
            $this->seekable = (bool) $this->getMetadata('seekable');
        }

        return $this->seekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        // Note that fseek returns 0 on success!
        if (!$this->isSeekable() || fseek($this->stream, $offset, $whence) !== 0) {
            throw new \RuntimeException('Could not seek in stream');
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        if (!$this->isSeekable() || rewind($this->stream) === false) {
            throw new \RuntimeException('Could not rewind stream');
        }
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if (is_null($this->writable)) {
            $this->writable = false;
            if ($mode = $this->getMetadata('mode')) {
                $this->writable = (
                    strstr($mode, 'x')
                    || strstr($mode, 'w')
                    || strstr($mode, 'c')
                    || strstr($mode, 'a')
                    || strstr($mode, '+')
                );
            }
        }

        return $this->writable;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!$this->isWritable() || ($written = fwrite($this->stream, $string)) === false) {
            throw new \RuntimeException('Could not write to stream');
        }
        // reset size and meta data, so that it will be recalculated on next call to getSize(), getMetadata()
        $this->size = null;
        $this->meta = null;

        return $written;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if (is_null($this->readable)) {
            $this->readable = false;
            if (($mode = $this->getMetadata('mode'))) {
                $this->readable = (strstr($mode, 'r') || strstr($mode, '+'));
            }
        }

        return $this->readable;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->isReadable() || ($data = fread($this->stream, $length)) === false) {
            throw new \RuntimeException('Could not read from stream');
        }

        return $data;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        if (!$this->isReadable() || ($contents = stream_get_contents($this->stream)) === false) {
            throw new \RuntimeException('Could not get contents of stream');
        }

        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if (is_null($this->meta) && $this->isAttached()) {
            $this->meta = stream_get_meta_data($this->stream);
        }

        if (is_null($key) === true) {
            return $this->meta;
        }

        return is_array($this->meta) && isset($this->meta[$key]) ? $this->meta[$key] : null;
    }
}
