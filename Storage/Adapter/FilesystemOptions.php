<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Zend\Cache\Storage\Adapter;

use Zend\Cache\Exception;

/**
 * These are options specific to the Filesystem adapter
 *
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Storage
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class FilesystemOptions extends AdapterOptions
{
    /**
     * Overwrite default namespace pattern
     *
     * @var string
     */
    protected $namespacePattern = '/^[a-z0-9_\+\-]*$/Di';

    /**
     * Namespace separator
     *
     * @var string
     */
    protected $namespaceSeparator = '-';

    /**
     * Overwrite default key pattern
     */
    protected $keyPattern = '/^[a-z0-9_\+\-]*$/Di';

    /**
     * Directory to store cache files
     *
     * @var null|string The cache directory
     *                  or NULL for the systems temporary directory
     */
    protected $cacheDir = null;

    /**
     * Used umask on creating a cache file
     *
     * @var int
     */
    protected $fileUmask = 0117;

    /**
     * Lock files on writing
     *
     * @var boolean
     */
    protected $fileLocking = true;

    /**
     * Block writing files until writing by another process finished.
     *
     * NOTE1: this only attempts if fileLocking is enabled
     * NOTE3: if disabled writing operations return false in part of a locked file
     *
     * @var boolean
     */
    protected $fileBlocking = true;

    /**
     * Used umask on creating a cache directory
     *
     * @var int
     */
    protected $dirUmask = 0007;

    /**
     * How much sub-directaries should be created?
     *
     * @var int
     */
    protected $dirLevel = 1;

    /**
     * Don't get 'fileatime' as 'atime' on metadata
     *
     * @var boolean
     */
    protected $noAtime = true;

    /**
     * Don't get 'filectime' as 'ctime' on metadata
     *
     * @var boolean
     */
    protected $noCtime = true;

    /**
     * Read control enabled ?
     *
     * If enabled a hash (readControlAlgo) will be saved and check on read.
     *
     * @var boolean
     */
    protected $readControl = false;

    /**
     * The used hash algorithm if read control is enabled
     *
     * @var string
     */
    protected $readControlAlgo = 'crc32';

    /**
     * Call clearstatcache enabled?
     *
     * @var boolean
     */
    protected $clearStatCache = true;

    /**
     * The adapter using these options
     * 
     * @var null|Filesystem
     */
    protected $target;

    /**
     * Filesystem adapter using this instance
     * 
     * @param  Filesystem $filesystem 
     * @return FilesystemOptions
     */
    public function setTarget(Filesystem $filesystem)
    {
        $this->target = $filesystem;
        $this->updateCapabilities();
        return $this;
    }

    /**
     * Update target capabilities
     * 
     * @return void
     */
    protected function updateCapabilities()
    {
        if (!$this->target) {
            return;
        }
        $this->target->updateCapabilities();
    }

    /**
     * Set namespace separator
     *
     * @param string $separator
     * @return Filesystem
     */
    public function setNamespaceSeparator($separator)
    {
        $this->namespaceSeparator = (string) $separator;
        $this->updateCapabilities();
        return $this;
    }

    /**
     * Get namespace separator
     *
     * @return string
     */
    public function getNamespaceSeparator()
    {
        return $this->namespaceSeparator;
    }

    /**
     * Set cache dir
     *
     * @param string $dir
     * @return Filesystem
     * @throws Exception\InvalidArgumentException
     */
    public function setCacheDir($dir)
    {
        if ($dir !== null) {
            if (!is_dir($dir)) {
                throw new Exception\InvalidArgumentException(
                    "Cache directory '{$dir}' not found or not a directoy"
                );
            } elseif (!is_writable($dir)) {
                throw new Exception\InvalidArgumentException(
                    "Cache directory '{$dir}' not writable"
                );
            } elseif (!is_readable($dir)) {
                throw new Exception\InvalidArgumentException(
                    "Cache directory '{$dir}' not readable"
                );
            }

            $dir = rtrim(realpath($dir), \DIRECTORY_SEPARATOR);
        }

        $this->cacheDir = $dir;
        return $this;
    }

    /**
     * Get cache dir
     *
     * @return null|string
     */
    public function getCacheDir()
    {
        if ($this->cacheDir === null) {
            $this->setCacheDir(sys_get_temp_dir());
        }

        return $this->cacheDir;
    }

    /**
     * Set file perm
     *
     * @param $perm
     * @return Filesystem
     */
    public function setFilePerm($perm)
    {
        $perm = $this->normalizeUmask($perm);

        // use umask
        return $this->setFileUmask(~$perm);
    }

    /**
     * Get file perm
     *
     * @return int
     */
    public function getFilePerm()
    {
        return ~$this->getFileUmask();
    }

    /**
     * Set file umask
     *
     * @param  $umask
     * @return Filesystem
     * @throws Exception\InvalidArgumentException
     */
    public function setFileUmask($umask)
    {
        $umask = $this->normalizeUmask($umask, function($umask) {
            if ((~$umask & 0600) != 0600 ) {
                throw new Exception\InvalidArgumentException(
                    'Invalid file umask or file permission: '
                    . 'need permissions to read and write files by owner'
                );
            } elseif ((~$umask & 0111) > 0) {
                throw new Exception\InvalidArgumentException(
                    'Invalid file umask or file permission: '
                    . 'executable cache files are not allowed'
                );
            }
        });

        $this->fileUmask = $umask;
        return $this;
    }

    /**
     * Get file umask
     *
     * @return int
     */
    public function getFileUmask()
    {
        return $this->fileUmask;
    }

    /**
     * Set file locking
     *
     * @param  bool $flag
     * @return Filesystem
     */
    public function setFileLocking($flag)
    {
        $this->fileLocking = (bool)$flag;
        return $this;
    }

    /**
     * Get file locking
     *
     * @return bool
     */
    public function getFileLocking()
    {
        return $this->fileLocking;
    }

    /**
     * Set file blocking
     *
     * @param  bool $flag
     * @return Filesystem
     */
    public function setFileBlocking($flag)
    {
        $this->fileBlocking = (bool) $flag;
        return $this;
    }

    /**
     * Get file blocking
     *
     * @return bool
     */
    public function getFileBlocking()
    {
        return $this->fileBlocking;
    }

    /**
     * Set no atime
     *
     * @param  bool $flag
     * @return Filesystem
     */
    public function setNoAtime($flag)
    {
        $this->noAtime = (bool) $flag;
        $this->updateCapabilities();
        return $this;
    }

    /**
     * Get no atime
     *
     * @return bool
     */
    public function getNoAtime()
    {
        return $this->noAtime;
    }

    /**
     * Set no ctime
     *
     * @param  bool $flag
     * @return Filesystem
     */
    public function setNoCtime($flag)
    {
        $this->noCtime = (bool) $flag;
        $this->updateCapabilities();
        return $this;
    }

    /**
     * Get no ctime
     *
     * @return bool
     */
    public function getNoCtime()
    {
        return $this->noCtime;
    }

    /**
     * Set dir perm
     *
     * @param string|integer $perm
     * @return Filesystem
     */
    public function setDirPerm($perm)
    {
        $perm = $this->normalizeUmask($perm);

        // use umask
        return $this->setDirUmask(~$perm);
    }

    /**
     * Get dir perm
     *
     * @return int
     */
    public function getDirPerm()
    {
        return ~$this->getDirUmask();
    }

    /**
     * Set dir umask
     *
     * @param string|integer $umask
     * @return Filesystem
     * @throws Exception\InvalidArgumentException
     */
    public function setDirUmask($umask)
    {
        $umask = $this->normalizeUmask($umask, function($umask) {
            if ((~$umask & 0700) != 0700 ) {
                throw new Exception\InvalidArgumentException(
                    'Invalid directory umask or directory permissions: '
                    . 'need permissions to execute, read and write directories by owner'
                );
            }
        });

        $this->dirUmask = $umask;
        return $this;
    }

    /**
     * Get dir umask
     *
     * @return int
     */
    public function getDirUmask()
    {
        return $this->dirUmask;
    }

    /**
     * Set dir level
     *
     * @param  integer $level
     * @return Filesystem
     * @throws Exception\InvalidArgumentException
     */
    public function setDirLevel($level)
    {
        $level = (int) $level;
        if ($level < 0 || $level > 16) {
            throw new Exception\InvalidArgumentException(
                "Directory level '{$level}' must be between 0 and 16"
            );
        }
        $this->dirLevel = $level;
        return $this;
    }

    /**
     * Get dir level
     *
     * @return int
     */
    public function getDirLevel()
    {
        return $this->dirLevel;
    }

    /**
     * Set read control
     *
     * @param bool $flag
     * @return Filesystem
     */
    public function setReadControl($flag)
    {
        $this->readControl = (bool) $flag;
        return $this;
    }

    /**
     * Get read control
     *
     * @return bool
     */
    public function getReadControl()
    {
        return $this->readControl;
    }

    /**
     * Set real control algo
     *
     * @param  string $algo
     * @return Filesystem
     * @throws Exception\InvalidArgumentException
     */
    public function setReadControlAlgo($algo)
    {
        $algo = strtolower($algo);

        if (!in_array($algo, Utils::getHashAlgos())) {
            throw new Exception\InvalidArgumentException("Unsupported hash algorithm '{$algo}");
        }

        $this->readControlAlgo = $algo;
        return $this;
    }

    /**
     * Get read control algo
     *
     * @return string
     */
    public function getReadControlAlgo()
    {
        return $this->readControlAlgo;
    }

    /**
     * Set clear stat cache
     *
     * @param  bool $flag
     * @return Filesystem
     */
    public function setClearStatCache($flag)
    {
        $this->clearStatCache = (bool) $flag;
        return $this;
    }

    /**
     * Get clear stat cache
     *
     * @return bool
     */
    public function getClearStatCache()
    {
        return $this->clearStatCache;
    }

    /**
     * Normalize a umask and optionally apply a callback to it
     * 
     * @param  int|string $umask 
     * @param  callable $callback 
     * @return int
     */
    protected function normalizeUmask($umask, $callback = null)
    {
        if (is_string($umask)) {
            $umask = octdec($umask);
        } else {
            $umask = (int) $umask;
        }

        if (is_callable($callback)) {
            $callback($umask);
        }

        return $umask;
    }
}
