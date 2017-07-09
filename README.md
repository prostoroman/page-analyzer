# Page analyzer
Calculate frequency of words in html content (input string, file or URL).

```php
$analyzer = new PageAnalyzer\Analyzer();
$stats = $analyzer->analyse('http://www.lipsum.com/');
var_dump($stats);
```

## Options

Example, how to ignore content in noindex, exclude stop words from analysis and check presence in particular tags used by search engine algorythms in rankinkg.

```php
$options = [
  'ignoreNoindex' => true,
  'stopWords' => ['в', 'и', 'от', 'для'],
  'checkTags' => ['title', 'keywords', 'description', 'a', 'b,strong', 'h1,h2,h3,h4,h5,h6']
];
$analyzer = new PageAnalyzer\Analyzer(['stopWords' => $stopWords]);
var_dump($stats);
```

Please check: [the demo here](http://bistro-site.localhost/services/seo/analyzer)

Would appreciate any contributions.
Thank you!
