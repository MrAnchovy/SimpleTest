<?php

namespace Sins;

class TestFiles
{

    public function recursiveScan($paths) {
        if (!is_array($paths)) {
            $paths = array($paths);
        }
        foreach ($paths as $path) {
            $path = realpath($path);
            $objects = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
                RecursiveIteratorIterator::SELF_FIRST);
            foreach($objects as $name => $object){
                echo "$name\n";
            }
        }

    }

}
