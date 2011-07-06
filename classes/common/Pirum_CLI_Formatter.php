<?php
/**
 * Command line colorizer for Pirum.
 *
 * @package    Pirum
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Pirum_CLI_Formatter
{
    protected $styles = array(
        'ERROR_SECTION'   => array('bg' => 'red', 'fg' => 'white'),
        'INFO_SECTION'    => array('bg' => 'green', 'fg' => 'white'),
        'COMMENT_SECTION' => array('bg' => 'yellow', 'fg' => 'white'),
        'ERROR'           => array('fg' => 'red'),
        'INFO'            => array('fg' => 'green'),
        'COMMENT'         => array('fg' => 'yellow'),
    );
    protected $options    = array('bold' => 1, 'underscore' => 4, 'blink' => 5, 'reverse' => 7, 'conceal' => 8);
    protected $foreground = array('black' => 30, 'red' => 31, 'green' => 32, 'yellow' => 33, 'blue' => 34, 'magenta' => 35, 'cyan' => 36, 'white' => 37);
    protected $background = array('black' => 40, 'red' => 41, 'green' => 42, 'yellow' => 43, 'blue' => 44, 'magenta' => 45, 'cyan' => 46, 'white' => 47);
    protected $supportsColors;

    public function __construct()
    {
        $this->supportsColors = DIRECTORY_SEPARATOR != '\\' && function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }

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
        if (!$this->supportsColors) {
            return $text;
        }

        if ('NONE' == $style || !isset($this->styles[$style])) {
            return $text;
        }

        $parameters = $this->styles[$style];

        $codes = array();
        if (isset($parameters['fg'])) {
            $codes[] = $this->foreground[$parameters['fg']];
        }
        if (isset($parameters['bg'])) {
            $codes[] = $this->background[$parameters['bg']];
        }
        foreach ($this->options as $option => $value) {
            if (isset($parameters[$option]) && $parameters[$option])
            {
                $codes[] = $value;
            }
        }

        return "\033[".implode(';', $codes).'m'.$text."\033[0m";
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
