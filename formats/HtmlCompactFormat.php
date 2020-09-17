<?php
class HtmlCompactFormat extends FormatAbstract {
	const MIME_TYPE = 'text/html';

	public function stringify(){
		$extraInfos = $this->getExtraInfos();
		$title = htmlspecialchars($extraInfos['name']);
		$uri = htmlspecialchars($extraInfos['uri']);

		// Dynamically build buttons for all formats (except HTML)
		$formatFac = new FormatFactory();
		$formatFac->setWorkingDir(PATH_LIB_FORMATS);

		$buttons = '';
		$links = '';

		foreach($formatFac->getFormatNames() as $format) {
			if(strcasecmp($format, 'HTML') === 0) {
				continue;
			}

			$query = str_replace('format=Html', 'format=' . $format, htmlentities($_SERVER['QUERY_STRING']));
			$buttons .= $this->buildButton($format, $query) . PHP_EOL;

			$mime = $formatFac->create($format)->getMimeType();
			$links .= $this->buildLink($format, $query, $mime) . PHP_EOL;
		}

		$entries = '';
		$entryCounter = 0;
		foreach($this->getItems() as $item) {
			
			$entryCounter = $entryCounter + 1;
			$entryID = 'entry' . $entryCounter;
			$checked = '';
			if($entryCounter == 1)
			{
				$checked = 'checked';
			}
			
			$entryAuthor = $item->getAuthor() ? '<br /><p class="author">by: ' . $item->getAuthor() . '</p>' : '';
			$entryTitle = $this->sanitizeHtml(strip_tags($item->getTitle()));
			$entryUri = $item->getURI() ?: $uri;

			$entryTimestamp = '';
			if($item->getTimestamp()) {
				$entryTimestamp = '<time datetime="'
				. date('d-m-Y', $item->getTimestamp())
				. '">'
				. date('d-m-Y', $item->getTimestamp())
				. '</time>';
			}

			$entryContent = '';
			if($item->getContent()) {
				$entryContent = '<div class="content">'
				. $this->sanitizeHtml($item->getContent())
				. '</div>';
			}

			$entryEnclosures = '';
			if(!empty($item->getEnclosures())) {
				$entryEnclosures = '<div class="attachments"><p>Attachments:</p>';

				foreach($item->getEnclosures() as $enclosure) {
					$url = $this->sanitizeHtml($enclosure);

					$entryEnclosures .= '<li class="enclosure"><a href="'
					. $url
					. '">'
					. substr($url, strrpos($url, '/') + 1)
					. '</a></li>';
				}

				$entryEnclosures .= '</div>';
			}

			$entryCategories = '';
			if(!empty($item->getCategories())) {
				$entryCategories = '<div class="categories"><p>Categories:</p>';

				foreach($item->getCategories() as $category) {

					$entryCategories .= '<li class="category">'
					. $this->sanitizeHtml($category)
					. '</li>';
				}

				$entryCategories .= '</div>';
			}

			$entries .= <<<EOD
<section class="feeditem">
	  <input type="radio" name="collapse" id="{$entryID}" {$checked}>
	  <label class="handle" for="{$entryID}">
	    {$entryTimestamp}
	    <a class="itemtitle" target="_blank" href="{$entryUri}">{$entryTitle}</a>
	  </label>
	{$entryContent}
</section>
EOD;
		}

		$charset = $this->getCharset();

		/* Data are prepared, now let's begin the "MAGIE !!!" */
		$toReturn = <<<EOD
<!DOCTYPE html>
<html>
<head>
	<meta charset="{$charset}">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>{$title}</title>
	<link href="static/HtmlFormatCompact.css" rel="stylesheet">
	<link rel="icon" type="image/png" href="static/favicon.png">
	{$links}
	<meta name="robots" content="noindex, follow">
</head>
<body>
{$entries}
</body>
</html>
EOD;

		// Remove invalid characters
		ini_set('mbstring.substitute_character', 'none');
		$toReturn = mb_convert_encoding($toReturn, $this->getCharset(), 'UTF-8');
		return $toReturn;
	}

	public function display() {
		$this
			->setContentType(self::MIME_TYPE . '; charset=' . $this->getCharset())
			->callContentType();

		return parent::display();
	}

	private function buildButton($format, $query) {
		return <<<EOD
<a href="./?{$query}"><button class="rss-feed">{$format}</button></a>
EOD;
	}

	private function buildLink($format, $query, $mime) {
		return <<<EOD
<link href="./?{$query}" title="{$format}" rel="alternate" type="{$mime}">
EOD;
	}
}
