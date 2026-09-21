<?php
namespace PhlongTaIam;
class PathInfoBuilder {
	function buildByAcceptors($path, $finalAcceptors, $i)
	{
		$infos = array();
		foreach ($finalAcceptors as $acceptor) {
			$p = $i - $acceptor->strOffset + 1;
			$_info = $path[$p];
			$info = array("p" => $p,
						  "mw" => $_info["mw"] + $acceptor->mw,
                          "w" => $acceptor->w + $_info["w"],
                          "unk" => $acceptor->unk + $_info["unk"],
                          "type" => $acceptor->type);
			$infos[] = $info;
		}
		return $infos;
	}

	/**
	 * @param string[] $chars The full text split into individual UTF-8
	 *                        characters (see WordBreaker::buildPath()).
	 */
	function fallback($path, $leftBoundary, $chars, $i)
	{
		$_info = $path[$leftBoundary];
		$ch = $chars[$i];
		mb_regex_encoding("UTF-8");
		// U+0E48..U+0E4E are Thai tone marks and diacritics. They are written
		// as escapes rather than literally because they are combining marks:
		// on their own they have no base character to sit on, so the literal
		// form renders as unreadable floating glyphs in most editors.
		if (mb_ereg("[\u0E48-\u0E4E]", $ch)) {
			if ($leftBoundary != 0) {
				$pathAtLeftBoundary = $path[$leftBoundary];
				$leftBoundary = $pathAtLeftBoundary["p"];
			}
			return array("p" => $leftBoundary,
						 "mw" => 0,
						 "w" => 1 + $_info["w"],
						 "unk" => 1 + $_info["unk"],
						 "type" => "UNK");
		} else {
			return array("p" => $leftBoundary,
						 "mw" => $_info["mw"],
						 "w" => 1 + $_info["w"],
						 "unk" => 1 + $_info["unk"],
						 "type" => "UNK");
		}
	}

	function build($path, $finalAcceptors, $i, $leftBoundary, $chars) {
		$basicPathInfos = $this->buildByAcceptors($path, $finalAcceptors, $i);
		if (sizeof($basicPathInfos) > 0) {
			return $basicPathInfos;
		} else {
			return array($this->fallback($path, $leftBoundary, $chars, $i));
		}
	}
}
?>
