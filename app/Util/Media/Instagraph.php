<?php

namespace App\Util\Media;

class Instagraph
{

    public $_image = NULL;
    public $_output = NULL;
    public $_prefix = 'IMG';
    private $_width = NULL;
    private $_height = NULL;
    private $_tmp = NULL;
    private $_tmp2 = NULL;

    public static function factory($image, $output)
    {
        return new Instagraph($image, $output);
    }

    public function __construct($image, $output)
    {
        if(file_exists($image))
        {
            $this->_image = $image;
            list($this->_width, $this->_height) = getimagesize($image);
            $this->_output = $output;
        }
        else
        {
            throw new Exception('File not found. Aborting.');
        }
    }

    public function tempfile()
    {
        # copy original file and assign temporary name
        $this->_tmp = 'tmp/'.$this->_prefix.rand();
        # second tmpfile is for stuff that needs the alpha channel
        $this->_tmp2 = 'tmp/'.$this->_prefix.rand().".png";
        copy($this->_image, $this->_tmp);
        copy($this->_image, $this->_tmp2);
    }

    public function output()
    {
        # rename working temporary file to output filename
        unlink($this->_tmp2);
        rename($this->_tmp, $this->_output);
    }

    public function execute($command)
    {
        # remove newlines and convert single quotes to double to prevent errors
        $command = str_replace(array("\n", "'"), array('', '"'), $command);
        $command = escapeshellcmd($command);
        # execute convert program
        exec($command);
    }

    /** ACTIONS */

    public function colortone($input, $color, $level, $type = 0)
    {
        $args[0] = $level;
        $args[1] = 100 - $level;
        $negate = $type == 0? '-negate': '';

        $this->execute("convert
        {$input}
        ( -clone 0 -fill '$color' -colorize 100% )
        ( -clone 0 -colorspace gray $negate )
        -compose blend -define compose:args=$args[0],$args[1] -composite
        {$input}");
    }

    public function border($input, $color = 'black', $width = 20)
    {
        $this->execute("convert $input -bordercolor $color -border {$width}x{$width} $input");
    }

    public function frame($input, $frame)
    {
        $this->execute("convert $input ( '$frame' -resize {$this->_width}x{$this->_height}! -unsharp 1.5×1.0+1.5+0.02 ) -flatten $input");
    }

    public function brightnessContrast($b, $c)
    {
        // Calculate level values
        $z1 = (($c - 1) / (2 * $b * $c))*100;
        $z2 = (($c + 1) / (2 * $b * $c))*100;
        $command = "convert {$this->_tmp} -level {$z1}%,{$z2}% {$this->_tmp}";
        $this->execute($command);
    }

    public function vignette($input, $color_1 = 'none', $color_2 = 'black', $crop_factor = 1.5)
    {
        $crop_x = floor($this->_width * $crop_factor);
        $crop_y = floor($this->_height * $crop_factor);

        $this->execute("convert
        ( {$input} )
        ( -size {$crop_x}x{$crop_y}
        radial-gradient:$color_1-$color_2
        -gravity center -crop {$this->_width}x{$this->_height}+0+0 +repage )
        -compose multiply -flatten
        {$input}");
    }

    public function vignetteMath($input, $type = 'Multiply', $color_1 = 'none', $color_2 = 'black', $crop_factor = 1.5)
    {
        $crop_x = floor($this->_width * $crop_factor);
        $crop_y = floor($this->_height * $crop_factor);

        $this->execute("convert
        ( {$input} )
        ( -size {$crop_x}x{$crop_y}
        radial-gradient:$color_1-$color_2
        -gravity center -crop {$this->_width}x{$this->_height}+0+0 +repage )
        -compose {$type} -composite
        {$input}");
    }

    public function vignetteScreen($input, $color_1 = 'none', $color_2 = 'white', $crop_factor = 1.5)
    {
        $crop_x = floor($this->_width * $crop_factor);
        $crop_y = floor($this->_height * $crop_factor);

        $this->execute("convert
        ( {$input} )
        ( -size {$crop_x}x{$crop_y}
        radial-gradient:$color_1-$color_2
        -gravity center -crop {$this->_width}x{$this->_height}+0+0 +repage )
        -compose screen -composite
        {$input}");
    }

    public function vignetteOverlay($input, $color_1 = 'none', $color_2 = 'black', $crop_factor = 1.5)
    {
        $crop_x = floor($this->_width * $crop_factor);
        $crop_y = floor($this->_height * $crop_factor);

        $this->execute("convert
        ( {$input} )
        ( -size {$crop_x}x{$crop_y}
        radial-gradient:$color_1-$color_2
        -gravity center -crop {$this->_width}x{$this->_height}+0+0 +repage )
        -compose overlay -composite
        {$input}");
    }

    public function sepia($amount)
    {
        $sepia = "convert {$this->_tmp} -set colorspace RGB -sepia-tone 80% {$this->_tmp2}";
        $this->execute($sepia);
        $alpha = "convert {$this->_tmp2} -alpha set -background transparent -channel A -fx \"{$amount}\" {$this->_tmp2}";
        $this->execute($alpha);
        $composite = "composite -compose atop {$this->_tmp2} {$this->_tmp} {$this->_tmp}";
        $this->execute($composite);
    }

    public function colorBlend($color, $amount, $type)
    {
        $fill = "convert {$this->_tmp2} -floodfill +0+0 {$color} {$this->_tmp2}";
        $alpha = "convert {$this->_tmp2} -alpha set -background transparent -channel A -fx \"{$amount}\" {$this->_tmp2}";
        $composite = "convert {$this->_tmp} {$this->_tmp2}  -composite -compose {$type} {$this->_tmp}";
        $this->execute($fill);
        $this->execute($alpha);
        $this->execute($composite);
    }

    public function gradientOverlay($color_1, $color_2)
    {
        $this->execute("convert
        ( {$this->_tmp} )
        ( -size {$this->_width}x{$this->_height}
        gradient:$color_1-$color_2 +repage )
        -compose multiply -flatten
        {$this->_tmp}");
    }

    public function nashville()
    {
        $this->tempfile();
        $this->brightnessContrast(.95, 1.5);
        $this->sepia(.35);
        $command = "convert {$this->_tmp} -modulate 100,100,91.667 {$this->_tmp}";
        $this->execute($command);
        $this->vignetteScreen($this->_tmp, 'rgba(128,78,15,.5)', 'rgba(128,78,15,.65)');

        $this->output();
    }

    public function nineteenseventyseven()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,140,83.3 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.5);
        $this->output();
    }

    public function aden()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 115,100,100 -modulate 100,140,100 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.2);
        $this->colorBlend("#7d6918", 0.1, "Multiply");
        $this->output();
    }

    public function amaro()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,130,100 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.2, 1.1);
        $this->sepia(0.35);
        $this->colorBlend("#7d6918", 0.2, "Overlay");
        $this->output();
    }

    public function ashby()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,180,100 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1, 1.2);
        $this->sepia(0.5);
        $this->colorBlend("#7d6918", 0.35, "Lighten");
        $this->output();
    }

    public function brannan()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,90,98 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.1, 1.25);
        $this->sepia(0.4);
        $this->output();
    }

    public function brooklyn()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,100,102.7773 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.25, 1.25);
        $this->sepia(0.25);
        $this->colorBlend("#7fbbe3", 0.2, "Overlay");
        $this->output();
    }

    public function charmes()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,135,97.223 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.25, 1.25);
        $this->sepia(0.25);
        $this->colorBlend("#7d6918", 0.25, "Darken");
        $this->output();
    }

    public function clarendon()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,100,102.223 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.25,1.25);
        $this->sepia(.15);
        $this->colorBlend("#7fbbe3", 0.4, "Overlay");
        $this->output();
    }

    public function crema()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,90,98.889 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.15,1.25);
        $this->sepia(0.5);
        $this->colorBlend("#7d6918", 0.1, "Multiply");
        $this->output();
    }

    public function dogpatch()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,110,100 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1,1.5);
        $this->sepia(0.35);
        $this->output();
    }

    public function earlybird()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,90,97.222 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.15,1.25);
        $this->sepia(0.25);
        $this->vignette($this->_tmp, "#FFFFFF", "rgba(125,105,24,.2)");
        $this->output();
    }

    public function gingham()
    {
        $this->tempfile();
        $this->brightnessContrast(1.1, 1.1);
        $this->colorBlend('#e6e6e6', 1, 'screen');
        $this->output();
    }

    public function ginza()
    {
        $this->tempfile();
        $command = "convert {$this->_tmp} -modulate 100,135,98.222 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.15, 1.2);
        $this->sepia(0.25);
        $this->colorBlend("#7d6918", 0.2, "Darken");
        $this->output();
    }

    public function hefe()
    {
        $this->tempfile();
        $this->brightnessContrast(1.2, 1.5);
        $command = "convert {$this->_tmp} -modulate 100,140,90 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.4);
        $this->vignette($this->_tmp, "#FFFFFF", "rgba(0,0,0,.25)");
        $this->output();
    }

    public function helena()
    {
        $this->tempfile();
        $this->brightnessContrast(1.05, 1.05);
        $command = "convert {$this->_tmp} -modulate 100,135,100 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.5);
        $this->output();
    }

    public function hudson()
    {
        $this->tempfile();
        $this->brightnessContrast(1.2, 1.2);
        $command = "convert {$this->_tmp} -modulate 100,105,91.667 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.25);
        $this->vignette($this->_tmp, "#FFFFFF", "rgba(25,62,167,.25)");
        $this->output();
    }

    public function inkwell()
    {
        $this->tempfile();
        $this->brightnessContrast(1.25, .85);
        $command = "convert {$this->_tmp} -modulate 100,0,100 {$this->_tmp}";
        $this->execute($command);
        $this->output();
    }

    public function kelvin()
    {
        $this->tempfile();
        $this->brightnessContrast(1.1, 1.5);
        $command = "convert {$this->_tmp} -modulate 100,100,94.444 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(.15);
        $this->vignetteOverlay($this->_tmp, "rgba(128,78,15,.25)", "rgba(128,78,15,.5)");
        $this->output();
    }

    public function juno()
    {
        $this->tempfile();
        $this->brightnessContrast(1.15, 1.15);
        $command = "convert {$this->_tmp} -modulate 100,180,100 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.35);
        $this->colorBlend("#7fbbe3", 0.2, "Overlay");
        $this->output();
    }

    public function lark()
    {
        $this->tempfile();
        $this->brightnessContrast(1.3, 1.2);
        $command = "convert {$this->_tmp} -modulate 100,125,100 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.25);
        $this->output();
    }

    public function lofi()
    {
        $this->tempfile();
        $this->brightnessContrast(1, 1.5);
        $command = "convert {$this->_tmp} -modulate 100,110,100 {$this->_tmp}";
        $this->execute($command);
        $this->output();
    }

    public function ludwig()
    {
        $this->tempfile();
        $this->brightnessContrast(1.05, 1.05);
        $command = "convert {$this->_tmp} -modulate 100,200,100 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.25);
        $this->colorBlend("#7d6918", 0.1, "Overlay");
        $this->output();
    }

    public function maven()
    {
        $this->tempfile();
        $this->brightnessContrast(1.05, 1.05);
        $command = "convert {$this->_tmp} -modulate 100,175,100 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.35);
        $this->colorBlend("#9eaf1e", 0.25, "Darken");
        $this->output();
    }

    public function mayfair()
    {
        $this->tempfile();
        $this->brightnessContrast(1.15, 1.1);
        $command = "convert {$this->_tmp} -modulate 100,110,100 {$this->_tmp}";
        $this->execute($command);
        $this->vignette($this->_tmp, "#FFFFFF", "rgba(175,105,24,.4)");
        $this->output();
    }

    public function moon()
    {
        $this->tempfile();
        $this->brightnessContrast(1.4, .95);
        $command = "convert {$this->_tmp} -modulate 100,0,100 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.25);
        $this->output();
    }

    public function perpetua()
    {
        $this->tempfile();
        $this->brightnessContrast(1.25, 1.1);
        $command = "convert {$this->_tmp} -modulate 100,110,100 {$this->_tmp}";
        $this->execute($command);
        $this->gradientOverlay("rgba(0,91,154,.25)", "rgba(230,193,61,.25)");
        $this->output();
    }

    public function poprocket()
    {
        $this->tempfile();
        $this->brightnessContrast(1.2, 1);
        $this->sepia(0.15);
        $this->vignetteScreen($this->_tmp, "rgba(206,39,70,.75)", "black", 1);
        $this->output();
    }

    public function reyes()
    {
        $this->tempfile();
        $this->sepia(.5);
        $this->brightnessContrast(1.35, .6);
        $this->output();
    }

    public function rise()
    {
        $this->tempfile();
        $this->brightnessContrast(1.2, 1.25);
        $command = "convert {$this->_tmp} -modulate 100,90 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.25);
        $this->vignetteMath($this->_tmp, 'Lighten', 'none', 'rgba(230,193,61,.25)');
        $this->output();
    }

    public function sierra()
    {
        $this->tempfile();
        $this->brightnessContrast(.9, 1.5);
        $command = "convert {$this->_tmp} -modulate 100,100,91.7 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.25);
        $this->vignetteMath($this->_tmp, 'Screen', 'rgba(128,78,15,.5)', 'rgba(0,0,0,.65)');
        $this->output();
    }

    public function skyline()
    {
        $this->tempfile();
        $this->brightnessContrast(1.25, 1.25);
        $command = "convert {$this->_tmp} -modulate 100,120 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(.15);
        $this->output();
    }

    public function slumber()
    {
        $this->tempfile();
        $this->brightnessContrast(1, 1.25);
        $command = "convert {$this->_tmp} -modulate 100,125 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(.35);
        $this->colorBlend("#7d6918", 0.2, "Darken");
        $this->output();
    }

    public function stinson()
    {
        $this->tempfile();
        $this->brightnessContrast(1.1, 1.25);
        $command = "convert {$this->_tmp} -modulate 100,125 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.25);
        $this->colorBlend("#7d6918", 0.45, "Lighten");
        $this->output();
    }

    public function sutro()
    {
        $this->tempfile();
        $this->brightnessContrast(.9, 1.2);
        $command = "convert {$this->_tmp} -modulate 100,140,94.444 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.4);
        $this->vignetteMath($this->_tmp, 'Darken', 'none', 'rgba(0,0,0,.5)');
        $this->output();
    }

    public function toaster()
    {
        $this->tempfile();
        $this->brightnessContrast(.95, 1.5);
        $command = "convert {$this->_tmp} -modulate 100,100,91.667 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.25);
        $this->vignetteScreen($this->_tmp, '#804e0f', 'rgba(0,0,0,.25)', 1);
        $this->output();
    }

    public function valencia()
    {
        $this->tempfile();
        $this->brightnessContrast(1.1, 1.1);
        $this->sepia(.25);
        $this->colorBlend('rgb(230,193,61)', .1, 'Lighten');
        $this->output();
    }

    public function vesper()
    {
        $this->tempfile();
        $this->brightnessContrast(1.2, 1.15);
        $command = "convert {$this->_tmp} -modulate 100,130 {$this->_tmp}";
        $this->execute($command);
        $this->sepia(0.35);
        $this->colorBlend('rgb(125,105,24)', .25, 'Overlay');
        $this->output();
    }

    public function walden()
    {
        $this->tempfile();
        $this->sepia(0.15);
        $command = "convert {$this->_tmp} -modulate 100,140 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.25, .8);
        $this->colorBlend('hsl(66,79%,72%', .1, 'Darken');
        $this->output();
    }

    public function willow()
    {
        $this->tempfile();
        $this->sepia(0.1);
        $this->brightnessContrast(1.25, .85);
        $command = "convert {$this->_tmp} -modulate 100,5 {$this->_tmp}";
        $this->execute($command);
        $this->output();
    }

    public function xpro()
    {
        $this->tempfile();
        $this->sepia(.25);
        $this->vignette($this->_tmp, 'rgba(0,91,154,.35)', 'rgba(0,0,0,.65)', 1);
        $command = "convert {$this->_tmp} -modulate 100,110,97.222 {$this->_tmp}";
        $this->execute($command);
        $this->brightnessContrast(1.75, 1.25);
        $this->output();
    }
}

?>
