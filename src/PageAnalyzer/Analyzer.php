<?php
namespace PageAnalyzer;

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

    /**
     * Contain cashed Tag Words
     *
     * @var array
     */
    public $cachedTagWords = [];

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
        $stats = [];
        $this->dom = $this->parser->load($string);
        $stats['_total-with-stopwords'] = count($this->words);

        $this->excludeNoindexTags();
        $this->words = $this->getWords(html_entity_decode(strip_tags($dom->outerHtml)));
        $this->excludeStopWords();

        $this->stats['_total'] = count($this->words);

        foreach ($words as $word) {
            $word = mb_strtolower($word, 'UTF-8');
            $root = $this->stemmer ? $this->stemmer->stem_word($word) : $word;

            $this->stats[$root]['count'] = empty($this->stats[$root]['count']) ? 1 : $this->stats[$root]['count'] + 1;
            $this->stats[$root]['percentage'] = round($this->stats[$root]['count'] / $this->stats['_total'] * 100, 2);

            foreach ($this->options['checkMetaTags'] as $tag) {
                $this->checkWordInTags($word, $root, 'meta[name="'.$tag.'"]', 'content');
            }

            foreach ($this->options['checkTags'] as $tag) {
                $this->checkWordInTags($word, $root, $tag);
            }

            if (empty($stats[$root]['forms'])) {
                $stats[$root]['forms'][] = $word;
            } elseif (!in_array($word, $stats[$root]['forms'])) {
                $stats[$root]['forms'][] = $word;
            }
        }
        arraySortByColumn($stats, 'count', SORT_DESC);

        $this->stats = $stats;

        return $this->stats;
    }

    /**
     * Check presence of particular word in array
     *
     * @param string $word
     * @param string $root
     * @param string $tags
     * @param string $attr
     * @return boolean
     */
    public function checkWordInTags($word, $root, $tags, $attr = '')
    {
        if (empty($this->cachedTagWords[$tags])) {
            $this->cachedTagWords[$tags] = $this->getTagsWords($tags, $attr);
        }

        if (empty($this->stats[$root][$tags]) || !$this->stats[$root][$tags]) {
            $this->stats[$root][$tags] = in_array($word, $words = $this->cachedTagWords[$tags]) ? 1 : 0;
        }
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
     * Get words from tags
     *
     * @param string $tags
     */
    protected function getTagsWords($query, $attr = '')
    {
        $nodes = $this->dom->find($query);
        $words = [];
        foreach ($nodes as $node) {
            $text = $attr ? $node->getAttribute($attr) : $node->text;
            $words = array_merge($words, $this->getWords($text));
        }

        return $words;
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
