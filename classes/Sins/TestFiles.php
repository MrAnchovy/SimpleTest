<?php

namespace Sins;

use FilesystemIterator;

class TestFiles
{

    /**
     * Set to true to include files in all directories, otherwise only those matching
     * $testDirPattern will be scanned and in addition, no subdirectories of a
     * directory matching $this->ignoreDirPattern will be scanned.
    **/
    public $allDirs = false;

    /**
     * Set to true to include all files in scanned directories, otherwise only those
     * matching $testFilePattern will be included.
    **/
    public $allFiles = false;

    /**
     * Directories to ignore. The default ignores e.g. `.git`.
    **/
    public $ignoreDirPattern = '@^\.@';

    /**
     * Base directory to strip from filepaths.
    **/
    public $stripBase;

    /**
     * Directories to include. The default includes anything starting with test.
    **/
    public $testDirPattern = '@^(test)@i';

    /**
     * Files to include. The default includes anything starting with test_ or
     * testsuite_
    **/
    public $testFilePattern = '@^(test_|testsuite_)@i';

    /**
     * Scan recursively.
    **/
    public $recursive = true;

    /**
     * Scan recursively for test/testsuite files.
     *
     * @param  string  Path to search.
     * @param  bool    If false, ignore files until a directory matching
     *                 $this->testDirPattern is found.
    **/
    public function scan($path, $files = true) {

        $found = array();
        if (is_array($path)) {
            foreach($path as $onePath) {
                $found = array_merge($found, $this->recursiveScan($onePath));
            }
        }
        $entries = new FilesystemIterator(realpath($path), FilesystemIterator::SKIP_DOTS);
        foreach ($entries as $entryPath => $entry) {
            $entryFilename = $entry->getFilename();
            if ($entry->isDir()) {
                // check this directory is not to be ignored
                if ($this->recursive 
                    && ($this->allDirs || !preg_match($this->ignoreDirPattern, $entryFilename)))
                {
                    // set the flag if files in this directory are to be included
                    $subFiles = $files || preg_match($this->testDirPattern, $entryFilename);
                    // recurse, merging the results
                    $found = array_merge($found, $this->scan($entryPath, $subFiles));
                }
            } else {
                if ($files) {
                    if ($this->allFiles
                        || preg_match($this->testFilePattern, $entryFilename, $matches))
                    {
                        if ($this->stripBase && substr($entryPath, 0, strlen($this->stripBase)) === $this->stripBase) {
                            $found[] = substr($entryPath, strlen($this->stripBase));
                        } else {
                            $found[] = $entryPath;
                        }
                    }
                }
            }
        }
        return $found;
    }
}
