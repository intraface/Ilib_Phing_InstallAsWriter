<?php
/**
 * <code>
 * foreach (new RecursiveFileIterator(dirname(__FILE__)) as $item) {
 *     // Because $item is actually an SPLFileInfo object, echo gives you the absolute path from __toString() magic.
 *     echo $item . PHP_EOL;
 * }
 * </code>
 */
class RecursiveFileIterator extends RecursiveIteratorIterator
{
    /**
     * Takes a path to a directory, checks it, and then recurses into it.
     *
     * @param string $path directory to iterate
     *
     * @return void
     */
    public function __construct($path)
    {
        // Use realpath() and make sure it exists; this is probably overkill, but I'm anal.
        $path = realpath($path);

        if (!file_exists($path)) {
            throw new Exception("Path $path could not be found.");
        } elseif (!is_dir($path)) {
            throw new Exception("Path $path is not a directory.");
        }

        // Use RecursiveDirectoryIterator() to drill down into subdirectories.
        parent::__construct(new RecursiveDirectoryIterator($path));
    }
}

/**
 * // Same usage as above, but you can indicate allowed extensions with the optional second argument.
 *
 * <code>
 * foreach (new RecursiveFileFilterIterator(dirname(__FILE__) . '/src', 'php,js,gif,jpg,xml,htaccess') as $item) {
 *     // This is an SPLFileInfo object.
 *     echo $item . PHP_EOL;
 * }
 * </code>
 */
class RecursiveFileFilterIterator extends FilterIterator
{
    /**
     * acceptable extensions - array of strings
     */
    protected $ext = array();

    /**
     * Takes a path and shoves it into our earlier class.
     * Turns $ext into an array.
     *
     * @param $path directory to iterate
     * @param $ext comma delimited list of acceptable extensions
     *
     * @return void
     */
    public function __construct($path, $ext = 'php')
    {
        $this->ext = explode(',', $ext);
        parent::__construct(new RecursiveFileIterator($path));
    }

    /**
     * Checks extension names for files only.
     */
    public function accept()
    {
        $item = $this->getInnerIterator();

        // If it's not a file, accept it.
        if (!$item->isFile()) {
            return TRUE;
        }

        // If it is a file, grab the file extension and see if it's in the array.
        return in_array(pathinfo($item->getFilename(), PATHINFO_EXTENSION), $this->ext);
    }
}

class InstallAsWriter
{
    private $dir;
    private $subdir;
    private $ignored = array();
    private $ignore;
    private $written = 0;
    
    function __construct($dir, $subdir, $ignore = array())
    {
        $this->dir = $dir;
        $this->subdir = $subdir;
        $this->ignore = $ignore;
    }

    function write()
    {
        foreach (new RecursiveFileFilterIterator($this->dir . $this->subdir, 'php,js,png,gif,jpg,xml,htaccess') as $item) {
            // This is an SPLFileInfo object.
            $file = str_replace($this->dir, '', $item);
            $orig_file = substr(str_replace(DIRECTORY_SEPARATOR, '/', $file), 1);
            if (in_array(str_replace('src/www/', '', $orig_file), $this->ignore)) {
                $this->ignored[] = $orig_file; 
                continue;   
            }
            $new_filename =  str_replace(substr($this->subdir, 1), '', $orig_file);
            $new_filename = str_replace('www/', '', $new_filename);
            // $pfm->addInstallAs($orig_file, $new_filename);
            echo '<install as="'.$new_filename.'" name="'.$orig_file.'" />' . "\n";
            $this->written++;
        }
    }
    
    function getIgnored()
    {
        return $this->ignored;
    }
    
    function getWritten()
    {
        return $this->written;
    }
}

$d = '/home/lsolesen/workspace/carmakoma.com/src';
$dir = '/www/';
$ignore = array('www/config.local.php', 'www/site/config.local.php', 'www/site/config.local.example.php');

$writer = new InstallAsWriter($d, $dir, $ignore);
$writer->write();
print_r($writer->getWritten());
print_r($writer->getIgnored());