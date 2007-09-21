<?php
/* Really Simple Captcha */

/*  Copyright 2007 Takayuki Miyoshi (email: takayukister at gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class tam_captcha {

	function tam_captcha() {
		$this->chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$this->char_length = 4;
		$this->fonts = array(
			dirname(__FILE__) . '/gentium/GenAI102.TTF',
			dirname(__FILE__) . '/gentium/GenAR102.TTF',
			dirname(__FILE__) . '/gentium/GenI102.TTF',
			dirname(__FILE__) . '/gentium/GenR102.TTF');
		$this->tmp_dir = dirname(__FILE__) . '/tmp/';
		$this->img_size = array(72, 24);
		$this->bg = array(255, 255, 255);
		$this->fg = array(0, 0, 0);
		$this->base = array(6, 18);
		$this->font_size = 14;
		$this->font_char_width = 15;
		$this->img_type = 'png';
	}

	function generate_random_word() {
		$word = '';
		for ($i = 0; $i < $this->char_length; $i++) {
			$pos = mt_rand(0, strlen($this->chars) - 1);
			$char = $this->chars[$pos];
			$word .= $char;
		}
		return $word;
	}

	function generate_image($prefix, $captcha) {
		$filename = null;
		if ($im = imagecreatetruecolor($this->img_size[0], $this->img_size[1])) {
			$bg = imagecolorallocate($im, $this->bg[0], $this->bg[1], $this->bg[2]);
			$fg = imagecolorallocate($im, $this->fg[0], $this->fg[1], $this->fg[2]);
			imagefill($im, 0, 0, $bg);
			$x = $this->base[0] + mt_rand(-2, 2);
			for ($i = 0; $i < strlen($captcha); $i++) {
				$font = $this->fonts[array_rand($this->fonts)];
				imagettftext($im, $this->font_size, mt_rand(-2, 2), $x, $this->base[1] + mt_rand(-2, 2), $fg, $font, $captcha[$i]);
				$x += $this->font_char_width;
			}
			switch ($this->img_type) {
				case 'jpeg':
					$filename = $prefix . '.jpeg';
					imagejpeg($im, $this->tmp_dir . $filename);
					break;
				case 'gif':
					$filename = $prefix . '.gif';
					imagegif($im, $this->tmp_dir . $filename);
					break;
				case 'png':
				default:
					$filename = $prefix . '.png';
					imagepng($im, $this->tmp_dir . $filename);
			}
			imagedestroy($im);
		}
		if ($fh = fopen($this->tmp_dir . $prefix . '.php', 'w')) {
			fwrite($fh, '<?php $captcha = "' . $captcha . '"; ?>');
			fclose($fh);
		}
		return $filename;
	}

	function check($prefix, $response) {
		if (is_readable($this->tmp_dir . $prefix . '.php')) {
			include($this->tmp_dir . $prefix . '.php');
			if (0 == strcasecmp($response, $captcha))
				return true;
		}
		return false;
	}

	function remove($prefix) {
		$suffixes = array('.jpeg', '.gif', '.png', '.php');
		foreach ($suffixes as $suffix) {
			$file = $this->tmp_dir . $prefix . $suffix;
			if (is_file($file))
				unlink($file);
		}
	}
}

?>