<?php
namespace Drush\SiteAlias;

use Symfony\Component\Finder\Finder;

/**
 * Discover alias files named:
 *
 * - sitename.site.yml: contains multiple aliases, one for each of the
 *     environments of 'sitename'.
 *
 * Drush aliases that contain both a site name and an environment
 * (e.g. @site.env) will cause Drush to find the file named after
 * the respective site name and retreive the specified environment
 * record.
 *
 * Sites may also define a special alias file self.site.yml, which
 * may be stored in the drush/site-aliases directory relative to either
 * the Drupal root or the Composer root of the site. The environments
 * in this file will be merged with the available environments for
 * the element @self, however it is defined.
 */
class SiteAliasFileDiscovery
{
    protected $searchLocations = [];
    protected $groupAliasFiles;
    protected $depth = '== 0';

    /**
     * Add a location that alias files may be found.
     *
     * @param string $path
     * @return $this
     */
    public function addSearchLocation($path)
    {
        $this->groupAliasFiles = null;
        if (is_dir($path)) {
            $this->searchLocations[] = $path;
        }
        return $this;
    }

    /**
     * Return all of the paths where alias files may be found.
     * @return string[]
     */
    public function searchLocations()
    {
        return $this->searchLocations;
    }


    /**
     * Set the search depth for finding alias files
     *
     * @param string|int $depth (@see \Symfony\Component\Finder\Finder::depth)
     * @return $this
     */
    public function depth($depth)
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * Find an alias file SITENAME.site.yml in one
     * of the specified search locations.
     *
     * @param string $siteName
     * @return string|bool
     */
    public function findSingleSiteAliasFile($siteName)
    {
        $desiredFilename = "$siteName.site.yml";
        foreach ($this->searchLocations as $dir) {
            $check = "$dir/$desiredFilename";
            if (file_exists($check)) {
                return $check;
            }
        }
        return false;
    }

    /**
     * Obsolete
     *
     * @param string $groupName
     * @return string|bool
     */
    public function findGroupAliasFile($groupName)
    {
        return false;
    }

    /**
     * Obsolete
     *
     * @return string[]
     */
    public function findAllGroupAliasFiles()
    {
        return [];
    }

    /**
     * Return a list of all SITENAME.site.yml files in any of
     * the search locations.
     *
     * @return string[]
     */
    public function findAllSingleAliasFiles()
    {
        return $this->searchForAliasFiles('*.site.yml');
    }

    /**
     * Return all of the legacy alias files used in previous Drush versions.
     *
     * @return string[]
     */
    public function findAllLegacyAliasFiles()
    {
        return array_merge(
            $this->searchForAliasFiles('*.alias.drushrc.php'),
            $this->searchForAliasFiles('*.aliases.drushrc.php')
        );
    }

    /**
     * Obsolete.
     *
     * @return string[]
     */
    protected function findUnnamedGroupAliasFiles()
    {
        return [];
    }

    /**
     * Obsolete
     *
     * @return string[]
     */
    protected function groupAliasFileCache()
    {
        return [];
    }

    /**
     * Create a Symfony Finder object to search all available search locations
     * for the specified search pattern.
     *
     * @param string $searchPattern
     * @return Finder
     */
    protected function createFinder($searchPattern)
    {
        $finder = new Finder();
        $finder->files()
            ->name($searchPattern)
            ->in($this->searchLocations)
            ->depth($this->depth);
        return $finder;
    }

    /**
     * Return a list of all alias files matching the provided pattern.
     *
     * @param string $searchPattern
     * @return string[]
     */
    protected function searchForAliasFiles($searchPattern)
    {
        if (empty($this->searchLocations)) {
            return [];
        }
        $finder = $this->createFinder($searchPattern);
        $result = [];
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $result[] = $path;
        }
        return $result;
    }

    /**
     * Return a list of all alias files with the specified extension.
     *
     * @param string $filenameExensions
     * @return string[]
     */
    protected function searchForAliasFilesKeyedByBasenamePrefix($filenameExensions)
    {
        if (empty($this->searchLocations)) {
            return [];
        }
        $searchPattern = '*' . $filenameExensions;
        $finder = $this->createFinder($searchPattern);
        $result = [];
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $key = $this->extractKey($file->getBasename(), $filenameExensions);
            $result[$key] = $path;
        }
        return $result;
    }

    // TODO: Seems like this could just be basename()
    protected function extractKey($basename, $filenameExensions)
    {
        return str_replace($filenameExensions, '', $basename);
    }
}
