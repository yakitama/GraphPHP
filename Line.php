<?php

class LineGraph {
	private $render;
	private $sizex;
	private $sizey;
	private $render_sizex;
	private $render_sizey;
	private $data;
	private $line_param;
	private $x_axis_min;
	private $x_axis_max;

	const INTERNAL_RENDER_MUL = 4;
	const DEFAULT_LINE_COLOR_RED = 255;
	const DEFAULT_LINE_COLOR_GREEN = 255;
	const DEFAULT_LINE_COLOR_BLUE = 255;
	
	function __construct ($sizex, $sizey)
	{
		$this->data = array();
		$this->line_param = array();
		$this->x_axis_min = FALSE;
		$this->x_axis_max = FALSE;
		$this->init($sizex, $sizey);
	}

	public function init ($sizex = 0, $sizey = 0)
	{
		$this->sizex = ($sizex == 0) ? $this->sizex : $sizex;
		$this->sizey = ($sizey == 0) ? $this->sizey : $sizey;
		$this->render_sizex = $this->sizex * $this::INTERNAL_RENDER_MUL;
		$this->render_sizey = $this->sizey * $this::INTERNAL_RENDER_MUL;
		$this->render = imagecreatetruecolor($this->render_sizex, $this->render_sizey);
		imageantialias($this->render, TRUE);
	}

	public function add_line ( $name )
	{
		if ( isset($this->data[$name]) ) {
			return FALSE;
		}
		$this->data[$name] = array();
		$this->line_param[$name] = array();
		$this->set_line_color($name, $this::DEFAULT_LINE_COLOR_RED, $this::DEFAULT_LINE_COLOR_GREEN, $this::DEFAULT_LINE_COLOR_BLUE);
		$this->set_line_width($name, 1);
		return TRUE;
	}

	public function set_line_color ( $line_name, $r, $g, $b )
	{
		$this->line_param[$line_name]['color'] = imagecolorallocate($this->render, $r, $g, $b);
	}

	public function set_line_width ( $line_name, $width )
	{
		$this->line_param[$line_name]['width'] = $width * $this::INTERNAL_RENDER_MUL;
	}

	public function add_point ( $line_name, $x, $y )
	{
		if ( !isset($this->data[$line_name]) ) {
			return FALSE;
		}
		$this->data[$line_name][$y] = $x;
	}

	public function render_graph ( $filepath )
	{
		require_once('imageline.php');

		// x 軸の最大値と最小値が設定されていなければ、ここで自動設定する
		if ( $this->x_axis_min === FALSE ) {
			$this->set_min_of_x();
		}
		if ( $this->x_axis_max === FALSE ) {
			$this->set_max_of_x();
		}

		// 各軸の最大値と最小値を取得する
		$x_max = $this->get_x_max();
		$x_min = $this->get_x_min();
		$y_max = $this->get_y_max();
		$y_min = $this->get_y_min();

		// グラフ描画エリアの左上と右下を計算する
		// 描画エリアは画像サイズの 10% マージンの内部
		$graph_area_start = array('x'=>                      intval($this->render_sizex / 10), 'y'=>                      intval($this->render_sizey / 10));
		$graph_area_end   = array('x'=>$this->render_sizex - intval($this->render_sizex / 10), 'y'=>$this->render_sizey - intval($this->render_sizey / 10));

		// 目盛りを描画する
		// まだ未実装だけど

		// グラフを描画する
		$graph_area_x_size = $graph_area_end['x'] - $graph_area_start['x'];
		$graph_data_x_range = $this->x_axis_max - $this->x_axis_min;
		$graph_area_y_size = $graph_area_end['y'] - $graph_area_start['y'];
		$graph_data_y_range = $y_max - $y_min;

		foreach ( $this->data as $line_name => $line ) {
			ksort($line);
			$first_plot = TRUE;
			foreach ( $line as $y => $x ) {
				if ( $first_plot !== TRUE ) {
					$prev_plot_y = $graph_area_end['y'] - ($graph_area_y_size / $graph_data_x_range) * ($prev_x - $this->x_axis_min);
					$now_plot_y = $graph_area_end['y'] - ($graph_area_y_size / $graph_data_x_range) * ($x - $this->x_axis_min);
					$prev_plot_x = $graph_area_start['x'] + ($graph_area_x_size / $graph_data_y_range) * ($prev_y - $y_min);
					$now_plot_x = $graph_area_start['x'] + ($graph_area_x_size / $graph_data_y_range) * ($y - $y_min);
					imagelinethick($this->render, $prev_plot_x, $prev_plot_y, $now_plot_x, $now_plot_y, $this->line_param[$line_name]['color'], $this->line_param[$line_name]['width']);
				}
				$prev_x = $x;
				$prev_y = $y;
				$first_plot = FALSE;
			}
		}

		// グラフエリアの枠を描画する
		$graph_area_border_color = imagecolorallocate($this->render, 200, 200, 200);
		imagelinethick($this->render, $graph_area_start['x'], $graph_area_start['y'], $graph_area_start['x'], $graph_area_end['y'], $graph_area_border_color, 10);
		imagelinethick($this->render, $graph_area_start['x'], $graph_area_end['y'], $graph_area_end['x'], $graph_area_end['y'], $graph_area_border_color, 10);

		// 出力する
		$resizedim = imagecreatetruecolor($this->sizex, $this->sizey);
		imageantialias($resizedim, TRUE);
		imagecopyresampled($resizedim, $this->render, 0, 0, 0, 0, $this->sizex, $this->sizey, imagesx($this->render), imagesy($this->render));
		imagepng($resizedim, $filepath);
	}

	public function set_min_of_x ( $value = FALSE )
	{
		if ( $value !== FALSE ) {
			$this->x_axis_min = $value;
		} else {
			// 自動設定のときは、登録されているデータの最大値と最小値の差を取って、
			// その差の5%下を最低値にする
			$max = $this->get_x_max();
			$min = $this->get_x_min();
			$this->x_axis_min = $min - (($max - $min) * 0.05);
		}
	}

	public function set_max_of_x ( $value = FALSE )
	{
		if ( $value !== FALSE ) {
			$this->x_axis_max = $value;
		} else {
			// 自動設定のときは、登録されているデータの最大値と最小値の差を取って、
			// その差の5%上を最大値にする
			$max = $this->get_x_max();
			$min = $this->get_x_min();
			$this->x_axis_max = $max + (($max - $min) * 0.05);
		}
	}

	private function get_x_min ()
	{
		$min = FALSE;
		foreach ( $this->data as $line ) {
			$min = ($min === FALSE) ? min($line) : min(min($line), $min);
		}
		return $min;
	}

	private function get_x_max ()
	{
		$max = FALSE;
		foreach ( $this->data as $line ) {
			$max = ($max === FALSE) ? max($line) : max(max($line), $max);
		}
		return $max;
	}

	private function get_y_min ()
	{
		$min = FALSE;
		foreach ( $this->data as $line ) {
			$keys = array_keys($line);
			$min = ($min === FALSE) ? min($keys) : min(min($keys), $min);
		}
		return $min;
	}

	private function get_y_max ()
	{
		$max = FALSE;
		foreach ( $this->data as $line ) {
			$keys = array_keys($line);
			$max = ($max === FALSE) ? max($keys) : max(max($keys), $max);
		}
		return $max;
	}
}
