# サンプルコード

簡単な使い方

```PHP
<?php
require('GraphPHP/Line.php');

// インスタンスを作るときに出力する画像サイズを指定する
$graph = new LineGraph(1280, 720);

// グラフのデータ系列を追加する
$graph->add_line('regular_gasoline');

// グラフの線の太さや色を設定する
// set_line_color($graph_name, $color_red, $color_green, $color_blue)
$graph->set_line_color('regular_gasoline', 255, 100, 100);
// set_line_width($graph_name, $width)
$graph->set_line_width('regular_gasoline', 5);

// グラフにデータを追加する
// 入力できるデータは数値だけです（浮動小数点は入力できる）
// 例えば、ガソリンの価格推移をグラフにしたいとして、こんな感じの配列があるなら
$gasoline_price_array = array(135,135,136,135,136,136,137,137,137,138,137,137,138,138,139,139,139,139,140,140,140);
foreach ( $gasoline_price_array as $key => $price ) {
	$graph->add_point('regular_gasoline', $price, $key );
}

// 2本目のデータ系列を追加することもできます
// グラフに必要なデータを登録したら、レンダリングします。グラフは PNG ファイルとして指定のパスに生成されます。
$graph->render_graph('/tmp/output.png');
```

