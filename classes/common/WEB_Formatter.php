<?php
/**
 * Command line colorizer
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class WEB_Formatter
{
	protected $styles = array(
		'ERROR_SECTION'   => array('bg' => 'red', 'fg' => 'white'),
		'INFO_SECTION'    => array('bg' => 'green', 'fg' => 'white'),
		'COMMENT_SECTION' => array('bg' => 'yellow', 'fg' => 'white'),
		'ERROR'           => array('fg' => 'red'),
		'INFO'            => array('fg' => 'green'),
		'COMMENT'         => array('fg' => 'yellow'),
	);

	protected $cssMapping = array(
		'fg' => 'text-color',
		'bg' => 'background-color'
	);

   /**
     * Formats a text according to the given style or parameters.
     *
     * @param  string   $text  The text to style
     * @param  string   $style A style name
     *
     * @return string The styled text
     */
    public function format($text = '', $style = 'NONE')
	{
		echo '<p style="'.$this->getCssStyle($style).'">'.$text.'</p>'."\n";
	}

	private function getCssStyle($style)
	{
		$cssStyle = 'display:inline-block;';
		foreach ($this->cssMapping as $key => $cssAttrib) {
			if (isset($this->styles[$style][$key])) {
				$cssStyle .= $cssAttrib .': '.$this->styles[$style][$key].';';
			}
		}
		return $cssStyle;
	}

    /**
     * Formats a message within a section.
     *
     * @param string  $section  The section name
     * @param string  $text     The text message
     */
    public function formatSection($section, $text)
    {
		return
			'<dl>'.
				'<dt style="'.$this->getCssStyle($section.'_SECTION').'">'.$section.'</dt> '.
				'<dd style="'.$this->getCssStyle($section).'">'.$text.'</dd>'.
			'</dl>'."\n";
    }

	private function getText(array $args)
	{
		if (count($args) == 0) {
			throw new UnexpectedValueException('No text given');
		}

		if (count($args) == 1) {
			return $args[0];
		}

		return call_user_func_array('sprintf', $args);
	}

	public function error()
	{
		echo $this->formatSection('ERROR', $this->getText(func_get_args()));
		return 1;
	}

	public function comment()
	{
		echo $this->format($this->getText(func_get_args()), 'COMMENT');
	}

	public function info()
	{
		echo $this->formatSection('INFO', $this->getText(func_get_args()));
	}

    public function printUsage($usage)
    {
		echo implode(PHP_EOL, $usage).PHP_EOL.PHP_EOL;
    }

	public function printStackTrace($e)
	{
		echo '<pre>';
		echo $e->getTraceAsString();
		echo '</pre>';
	}
}
?>
