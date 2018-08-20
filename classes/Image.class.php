<?php
class Image
{
    const DIR = __DIR__ . "/../";
    public static function validate($params = array(), $files = array())
    {
        //verificando se o diretório do POST existe
        if (!is_dir($params['folder'])) {
            mkdir($params['folder']);
        }
        //validando se foi enviada uma imagem
        if (!isset($files['file']) || empty($files['file']) || $files['file']['name'] == null):
            return ["status" => "422", "data" => ["response" => "file is required"]];
        else:
            return true;
        endif;
    }
    public static function validateSize($method, $data = array()){
        if($method ==  'cut' && isset($data['w']) && isset($data['h'])){
            return true;
        }else{
            return false;
        }
    }
    
    public static function fixImageFromCamera($source, $file){
        
        //Verificando se a imagem veio de uma câmera
        try{
            $exif = @exif_read_data($file);
        
            $thumb_img = $source;
            if(!empty($exif['Orientation'])){
                switch ($exif['Orientation']) {
                    case 3:
                        $thumb_img = imagerotate($source, 180, 0);
                        break;
                    case 6:
                        $thumb_img = imagerotate($source, -90, 0);
                        break;
                    case 8:
                        $thumb_img = imagerotate($source, 90, 0);
                        break;
                    default:
                        $thumb_img = $source;
                } 
            }
        }catch(Exception $e){
            $thumb = $source;
        }
        
        return $thumb_img;
    }
    public static function compress($params = array(), $files = array(), $data = array())
    {
        try {
            //pegando informações da foto
            $info = getimagesize($files['file']['tmp_name']);
            $source = $files['file']['tmp_name'];
            //definindo a extensão da foto
            if ($info['mime'] == 'image/jpeg')
                $image = imagecreatefromjpeg($source);
            elseif ($info['mime'] == 'image/gif')
                $image = imagecreatefromgif($source);
            elseif ($info['mime'] == 'image/png')
                $image = imagecreatefrompng($source);
            
            $deg = 0;
            
            if(isset($data['rotate'])){
                if($data['rotate'] == 'left'){
                    $deg = 90;
                }
                if($data['rotate'] == 'right') {
                    $deg = 270;
                }
            }
            
            $image = self::fixImageFromCamera($image, $source);
            
            if($deg != 0)
                $image = imagerotate($image, $deg, 0);
            //gerando novo nome para foto
            $name = md5(uniqid()) . ".jpeg";
            //definindo diretório da foto
            $path = self::DIR . DIRECTORY_SEPARATOR . $params['folder'] . "/" . $name;
            //criando foto e colocando no diretório
            imagejpeg($image, $path, 80);
            return ["status" => "200", "data" => ["url" => $_SERVER['HTTP_ORIGIN'] . '/' . $params['folder'] . '/' . $name]];
        } catch (Exception $e) {
            return ["status" => "400", "data" => ["response" => "failed to compress image"]];
        }
    }
    public static function cut($params = array(), $files = array(), $data = array())
    {
        try {
            //pegando dimensões do POST
            $width = $data['w'];
            $height = $data['h'];
            //pegando informações da foto
            $info = getimagesize($files['file']['tmp_name']);
            list($w, $h) = getimagesize($files['file']['tmp_name']);
            //definindo extensão da foto
            if ($info['mime'] == 'image/jpeg')
                $thumb_img = imagecreatefromjpeg($files['file']['tmp_name']);
            elseif ($info['mime'] == 'image/gif')
                $thumb_img = imagecreatefromgif($files['file']['tmp_name']);
            elseif ($info['mime'] == 'image/png')
                $thumb_img = imagecreatefrompng($files['file']['tmp_name']);
            
            $deg = 0;
            
            if(isset($data['rotate'])){
                if($data['rotate'] == 'left'){
                    $deg = 80;
                }
                if($data['rotate'] == 'right') {
                    $deg = 270;
                }
            }
            
            $thumb_img = self::fixImageFromCamera($thumb_img, $files['file']['tmp_name']);
            
            if($deg != 0)
                $thumb_img = imagerotate($thumb_img, $deg, 0);
            
            $w = imagesx($thumb_img);
            $h = imagesy($thumb_img);
            //caso não exista os parametros de x e y para o crop
            if(!isset($data['x']) || !isset($data['y'])){
                //calculando coordenadas da foto
                $crop_x = ($w / 2) - ($width / 2);
                $crop_y = ($h / 2) - ($height / 2);
            }else{
                //define o x e y dos parametros
                $crop_x = $data['x'];
                $crop_y = $data['y'];
            }
            if(isset($data['src_w']) && isset($data['src_h'])){
                $src_w = $data['src_w'];
                $src_h = $data['src_h'];
            }else{
                $src_w = $w;
                $src_h = $w;
            }
            //definindo dimensões da foto
            $tmp_img = imagecreatetruecolor($width, $height);
            //gerando foto
            if(isset($data['src_w']) && isset($data['src_h'])){
                imagecopyresampled($tmp_img, $thumb_img, 0, 0, $crop_x, $crop_y, $width, $height, $src_w, $src_h);
            }else{
                imagecopyresampled($tmp_img, $thumb_img, 0, 0, $crop_x, $crop_y, $w, $h, $w, $h);
            }
            //gerando novo nome para foto
            $name = md5(uniqid()) . ".jpg";
            //criando a foto e colocando no diretório
            imagejpeg($tmp_img, $params['folder'] . '/' . $name, 80);
            return ["status" => "200", "data" => ["url" => $_SERVER['HTTP_ORIGIN'] . '/' . $params['folder'] . '/' . $name]];
        } catch (Exception $e) {
            return ["status" => "400", "data" => ["response" => "failed to cut image"]];
        }
    }
    public static function upload($params = array(), $files = array(), $data = array())
    {
        try {
            $path = null;
            //verificando se o file foi enviado
            if (!isset($files['folder']) && $params['folder']) {
                $path = self::DIR . DIRECTORY_SEPARATOR . $params['folder'];
            } else {
                $path = self::DIR;
            }
            //definindo data da imagem
            $date = new \DateTime("now");
            $nameTimestamp = $date->getTimestamp();
            //definindo nome da imagem
            $currentFileNameArray = explode(".", $files['file']['name']);
            $name = $currentFileNameArray[0] . '_' . $nameTimestamp . '.' . $currentFileNameArray[count($currentFileNameArray) - 1];
            //definindo diretório da imagem
            $url = $_SERVER['HTTP_ORIGIN'] . '/' . $params['folder'] . '/' . $name;
            $path = $path . DIRECTORY_SEPARATOR . $name;
            //movendo imagem para diretório
            if (move_uploaded_file($files['file']['tmp_name'], $path)) {
                return ["status" => "200", "data" => ["url" => $url]];
            } else {
                return ["status" => "400", "data" => ["response" => "failed to upload image"]];
            }
        } catch (Exception $e) {
            return ["status" => "400", "data" => ["response" => "failed to upload image"]];
        }
    }
    /**
     * @desc Metodo que verifica o tipo de imagem
     * @param $file
     * @return null|string
     */
    public static function getMime($file)
    {
        if (isset($file['file']['tmp_name']) && $file['file']['tmp_name'] != null) {
            $fileType = null;
            $mime = getimagesize($file['file']['tmp_name']);
            switch ($mime['mime']) {
                case 'image/jpeg' :
                case 'image/gif' :
                case 'image/png' :
                case 'image/jpg' :
                    $fileType = "img";
                    break;
                default :
                    $fileType = 'others';
                    break;
            }
            return $fileType;
        }
        return false;
    }
    public static function method_exist($method)
    {
        if (method_exists(get_called_class(), $method)):
            return true;
        else:
            return false;
        endif;
    }
}