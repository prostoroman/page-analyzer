<?php
namespace PageAnalyzer;

use PageAnalyzer\Helpers;

/**
 * Class Analyzer
 *
 * @package PageAnalyzer
 */
class Analyzer
{
    /**
     * List of options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Contains the stemmer class.
     *
     * @var class
     */
    public $stemmer;

    /**
     * Contains the parser class.
     *
     * @var class
     */
    public $parser;
    /**
     * Contains the Dom.
     *
     * @var class
     */
    public $dom;
    /**
     * Contains stats.
     *
     * @var array
     */
    public $stats = [];
    /**
     * Contains words array.
     *
     * @var array
     */
    public $words = [];
    /**
     * Returns the array of words from the string
     *
     * @param string $str
     * @return array
     */

    public function __construct(array $options = [])
    {
        $this->stemmer = new \Stem\LinguaStemRu();
        $this->parser = new \PHPHtmlParser\Dom();
        $this->setOptions($options);
    }

    public function setOptions(array $options = [])
    {
        $this->options = array_replace([
            'excludeNoindexTags' => true,
            'stopwords' => [],
            'checkMetaTags' => ['keyword', 'description'],
            'checkTags' => ['title', 'a', 'b', 'strong', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']
        ], $options);
    }
    /**
     * Returns the frequency statistics for string, file, or URL
     *
     * @param string $string
     * @return array
     */
    public function analyze($string)
    {
        $this->dom = $this->parser->load($string);
        $this->excludeNoindexTags();
        $this->words = $this->getWords(html_entity_decode(strip_tags($dom->outerHtml)));
        $this->excludeStopWords();

        $this->stats['_total'] = count($this->words);
        $roots = [];

        //title
        $title = $dom->find('title');
        $title = count($title) ? getWords($dom->find('title')[0]->text) : [];

        // meta
        $metaDescription = $dom->find('meta[name="description"]');
        $metaDescription = count($metaDescription) ? getWords($metaDescription->getAttribute('content')) : [];
        $metaKeywords = $dom->find('meta[name="keywords"]');
        $metaKeywords = count($metaKeywords) ? getWords($metaKeywords->getAttribute('content')) : [];

        // h1-h6
        $h16 = $dom->find('h1,h2,h3,h4,h5,h6');
        $headers = [];
        foreach ($h16 as $h) {
            $headers = array_merge($headers, getWords($h->text));
        }

        // strong-b
        $boldTags = $dom->find('strong,b');
        $bolds = [];
        foreach ($boldTags as $b) {
            $bolds = array_merge($bolds, getWords($b->text));
        }

        // links
        $linkTags = $dom->find('strong,b');
        $links = [];
        foreach ($linkTags as $l) {
            $links = array_merge($links, getWords($l->text));
        }

        $stemmer = $this->stemmer;
        foreach ($words as $word) {
            $word = mb_strtolower($word, 'UTF-8');
            $root = $stemmer->stem_word($word);
            $stats[$root]['count'] = empty($stats[$root]['count']) ? 1 : $stats[$root]['count'] + 1;
            $stats[$root]['percentage'] = round($stats[$root]['count'] / $stats['_total'] * 100, 2);

            if (empty($stats[$root]['title']) || !$stats[$root]['title']) {
                $stats[$root]['title'] = in_array($word, $title) ? 1 : 0;
            }

            if (empty($stats[$root]['keywords']) || !$stats[$root]['keywords']) {
                $stats[$root]['keywords'] = in_array($word, $metaKeywords) ? 1 : 0;
            }

            if (empty($stats[$root]['description']) || !$stats[$root]['description']) {
                $stats[$root]['description'] = in_array($word, $metaDescription) ? 1 : 0;
            }

            if (empty($stats[$root]['headers']) || !$stats[$root]['headers']) {
                $stats[$root]['headers'] = in_array($word, $headers) ? 1 : 0;
            }

            if (empty($stats[$root]['bolds']) || !$stats[$root]['bolds']) {
                $stats[$root]['bolds'] = in_array($word, $bolds) ? 1 : 0;
            }
            if (empty($stats[$root]['links']) || !$stats[$root]['links']) {
                $stats[$root]['links'] = in_array($word, $links) ? 1 : 0;
            }
            if (empty($roots[$root])) {
                $roots[$root][] = $word;
            } else if (!in_array($word, $roots[$root])) {
                $roots[$root][] = $word;
            }
        }
        arraySortByColumn($stats, 'count', SORT_DESC);
    }

    /**
     * Remove noindex tags, ussially used by Yandex
     *
     * @param Dom $dom
     * @return Dom $dom
     */
    protected function excludeNoindexTags()
    {
        if (!$this->options['excludeNoindexTags']) {
            return;
        }
        // remove <noindex></noindex>
        $noindexes = $this->dom->find('noindex');
        if (count($noindexes)) {
            $noindexes->delete();
            unset($noindexes);
        }

        // not solved for
        //<!--noindex-->...<!--/noindex-->
        //$html = preg_replace('/<!--noindex-->.*<!--\/noindex-->/si', '', $html);
        //echo $html;
    }

    /**
     * Remove stop words
     *
     * @param array $stopWords
     */
    protected function excludeStopWords()
    {
        if (!count($this->options['stopwords'])) {
            return;
        }
        //$stopWords = file_get_contents('seo/stopwords.txt');
        //$stopWords = $this->getWords($this->options['stopwords']);
        $this->stats['_total-with-stopwords'] = count($this->words);
        $this->words = array_diff($words, $stopWords);
    }

    /**
     * Returns the array of words from the string
     *
     * @param string $str
     * @return array
     */
    public function getWords($str)
    {
        return preg_split('/\P{L}+/u', mb_strtolower($str), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Sorts an array by column
     *
     * @param array $arr
     * @param string $col
     * @param constant $dir
     * @return array
     */
    public function arraySortByColumn(&$arr, $col, $dir = SORT_ASC)
    {
        $sort_col = array();

        foreach ($arr as $key => $row) {
            $sort_col[$key] = $row[$col];
        }

        array_multisort($sort_col, $dir, $arr);
    }
}
