<?php
class Upload
{
    /** Defines if will use ES6+ or not*/
    protected static $JSMode = 1; // 1 -> old ; 2 -> modern

    /** Object's name for frontend handle */
    private static $JSObject = "Upload";

    /** Frontend function to be called */
    private static $JSCall = "newUpload";

    /** Root dir */
    protected static $rootDir = null;

    /** This is the input name to be called in frontend, each binded to a specific profile or the same */
    protected static $inputs = array();

    /** Array with all available upload profiles */
    private static $profiles = array();

    /**
     * @param $name = profile name
     * @param $config = the profile setup array
     * 
     */
    public static function addProfile($name, $config)
    {
        /* $template = array(
          "url" => "where the request will be sent",
          "types" => array("jpeg", "jpg", "png"),
          "folder" => "./uploads/",
          "maxSize" => 260000,
          "maxFiles" => 10,
          "vars" => array(), // give additional variables
          ); */
        if (isset($config['folder'])) {
            $last = substr($config['folder'], -1);
            if ($last == '/' || $last == "\\") {
                $config['folder'] = substr($config['folder'], 0, -1);
            }
        }
        self::$profiles[$name] = array(
            "config" => $config,
            "key" => md5($name),
            "name" => $name
        );
    }

    /** 
     * @param $name || $key = profile identifier
     * @return array = profile config
     *  */
    public static function getProfile($profile)
    {
        if (array_key_exists($profile, self::$profiles)) {
            return self::$profiles[$profile];
        }
        $md5 = md5($profile);
        if (array_key_exists($md5, self::$profiles)) {
            return self::$profiles[$md5];
        }
        return false;
    }

    /** 
     * @param $input = input name on frontend
     * @param $profile = profile name for binded input
     */
    public static function addInput($input, $profile)
    {
        if (!isset(self::$inputs[$profile])) {
            self::$inputs[$profile] = [];
        }
        self::$inputs[$profile][] = $input;
    }

    /**
     * @param string $varKey = variable key to be added
     * @param string $varValue = variable value to be added
     * @param string $profile = which profile variable should be added
     */
    public static function addVar($varKey, $varValue, $profile)
    {
        self::$profiles[$profile]['vars'][] = [$varKey => $varValue];
    }

    /**
     * @param (string)$dir = root dir for the server
     */
    public static function setRootDir($dir)
    {
        self::$rootDir = $dir;
    }

    /**
     * @param (bool)$tags = Defines if output adds script tags as well
     * @param $profile = Which profile to output
     */
    public static function setProfiles($tags = true, $profile = "all")
    {
        foreach (self::$profiles as $profileName => $profileData) {
            unset(self::$profiles[$profileName]['config']['folder']);
        }
        if ($profile !== "all" && !isset($profile, self::$profiles)) {
            return false;
        }
        if ($profile === "all") {
            foreach (self::$inputs as $uploadProfile => $inputs) {
                if (array_key_exists($uploadProfile, self::$profiles)) {
                    self::$profiles[$uploadProfile]['inputNames'] = $inputs;
                }
            }
        } else {
            self::$profiles[$profile]['inputNames'] = self::$inputs[$profile];
        }
        if ($tags) {
            echo "<script type='text/javascript'>";
            echo (self::$JSMode == 1 ? "var" : "const") . " uploadProfiles = " . json_encode($profile == 'all' ? self::$profiles : self::$profiles[$profile]) . ";";
            echo "</script>";
            return true;
        }
        echo json_encode($profile == "all" ? self::$profiles : self::$profiles[$profile]);
        return true;
    }

    /**
     * @param $input = input name
     * @param $profile = which profile to bind
     * @param $tags = if tags will be echoed 
     */
    public static function setTo($input, $profile, $tags = true)
    {
        if ($tags) {
            echo "<script type='text/javascript'>";
            echo self::$JSObject . "." . self::$JSCall . "('" . $input . "','" . $profile . "');";
            echo "</script>";
            return true;
        }
        echo self::$JSObject . "." . self::$JSCall . "('" . $input . "','" . $profile . "');";
        return true;
    }

    /**
     * @param $fileName = file name...
     * @param $data = part of file contents
     * @param $folder = upload folder
     */
    public static function saveFile($fileName, $data, $folder)
    {
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
            chmod($folder, 0775);
        }
        $id = md5($fileName);
        $jsonData = array(
            "name" => $fileName,
            "date" => date("Y-m-d"),
            "status" => "uploading"
        );
        if (file_exists($folder . DIRECTORY_SEPARATOR . "upload.log.json")) {
            $log = json_decode(file_get_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json"), true);
            $log[$id] = $jsonData;
        } else {
            $log = array();
            $log[$id] = $jsonData;
        }
        file_put_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json", json_encode($log));
        self::removeTrash($folder);
        $file = @fopen($folder . DIRECTORY_SEPARATOR . $fileName, 'a');
        if ($file) {
            $data = explode(',', $data);
            fwrite($file, base64_decode(str_replace(" ", "+", $data[1])));
            fclose($file);
            return true;
        } else {
            // script doesn't have permission to create folders or files.
            return false;
        }
    }

    /**
     * @param $folder = clears up canceled uploaded files
     */
    private static function removeTrash($folder)
    {
        if (file_exists($folder . DIRECTORY_SEPARATOR . "upload.log.json")) {
            $json = json_decode(file_get_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json"), true);
            $date = date("Y-m-d");
            foreach ($json as $id => $data) {
                if ($data["date"] !== $date) {
                    @unlink($folder . DIRECTORY_SEPARATOR . $data["name"]);
                    unset($json[$id]);
                }
            }
            file_put_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json", json_encode($json));
        }
        return true;
    }

    /**
     * @param $fileName = file name...
     * @param $folder = folder where file is uploaded
     */
    public static function unsetLog($fileName, $folder)
    {
        if (file_exists($folder . DIRECTORY_SEPARATOR . "upload.log.json")) {
            $json = json_decode(file_get_contents($folder . DIRECTORY_SEPARATOR .  "upload.log.json"), true);
            unset($json[md5($fileName)]);
            if (empty($json)) {
                @unlink($folder . DIRECTORY_SEPARATOR . "upload.log.json");
                return true;
            } else {
                file_put_contents($folder . DIRECTORY_SEPARATOR . "upload.log.json", json_encode($json));
                return true;
            }
        }
    }

    /**
     * @param $path = path to delete file
     */
    public static function delete($path)
    {
        if (file_exists($path) && !preg_match("/(\.)(php|js|css|html)/", $path)) {
            if (unlink($path)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /** @param $source = creates a new file name from source name */
    public static function setNewName($source)
    {
        $aux = explode(".", $source);
        $extension = $aux[count($aux) - 1];
        unset($aux[count($aux) - 1]);
        $aux = implode('.', $aux);
        return preg_replace("/[^a-zA-Z0-9\.]/", "-", $aux) . md5($source) . md5(time()) . '.' . $extension;
    }

    /** @param $tags = defines if script tags will be appended to profile */
    public static function init($tags = true)
    {
        self::setProfiles($tags);
        foreach (self::$inputs as $profile => $inputs) {
            foreach ($inputs as $input) {
                self::setTo($input, $profile);
            }
        }
    }
}
