<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 09/24/2016
 * Time: 5:29 AM
 */

namespace BetterReflection\SourceLocator\Type;


use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\Reflector;

class DirectorySourceLocator implements SourceLocator
{

    /**
     * @var AggregateSourceLocator
     */
    private $aggregatedSourceLocator;


    /**
     * DirectorySourceLocator constructor.
     * @param $directory string|array directory to scan
     */
    public function __construct($directory = null)
    {
        $dirs = [];
        if ($directory) {
            if (is_string($directory)) {
                $dirs[] = $directory;
            } elseif (is_array($directory)) {
                $dirs = $directory;
            }
        }

        $sourceLocators = [];
        foreach ( $dirs as $dir) {
            $sourceLocators = array_merge($sourceLocators, $this->scan($dir));
        }

        $this->aggregatedSourceLocator = new AggregateSourceLocator($sourceLocators);

    }

    /**
     * scan target directory and resulted as SourceLocator[]
     * @param $dir string directory path
     * @return SourceLocator[]
     */
    private function scan($dir){
        $sourceLocators = [];
        $rdi = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        foreach ( new \RecursiveIteratorIterator($rdi) as $item) {
            if ($item->isFile() && pathinfo($item->getRealPath(), PATHINFO_EXTENSION) == 'php') {
                $sourceLocators[] = new SingleFileSourceLocator($item->getRealPath());
            }
        }
        return $sourceLocators;
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier)
    {
        return $this->aggregatedSourceLocator->locateIdentifier($reflector, $identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType)
    {
        return $this->aggregatedSourceLocator->locateIdentifiersByType($reflector, $identifierType);
    }


}