<?php
/**
 * Command line colorizer
 *
 * @author Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class WEB_Formatter
{
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
		echo '<p class="'.$style.'">'.$text.'</p>';
	}

    /**
     * Formats a message within a section.
     *
     * @param string  $section  The section name
     * @param string  $text     The text message
     */
    public function formatSection($section, $text)
    {
        $section = $style = array_key_exists($section, $this->styles) ? $section : 'INFO';
        $section = " $section ".str_repeat(' ', max(0, 5 - strlen($section)));
        $style .= '_SECTION';

        return sprintf("  %s %s\n", $this->format($section, $style), $text);
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

	public function exception($e)
	{
		return $this->error("%s (%s, %s)", $e->getMessage(), get_class($e), $e->getCode());
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
}
?>
