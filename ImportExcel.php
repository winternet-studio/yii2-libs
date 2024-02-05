<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportExcel extends Component {
	public $reader;
	public $spreadsheet;
	public $headerRow;
	public $headerNames;

	public function __construct($filename, $headerRow = true) {
		$this->reader = IOFactory::createReader('Xlsx');
		$this->spreadsheet = $this->reader->load($filename);
		$this->headerRow = $headerRow;
	}

	public function loadToArray($options = []) {
		$output = [];

		if (@$options['worksheetNumber']) {
			$worksheet = $this->spreadsheet->setActiveSheetIndex($options['worksheetNumber']-1);
		} elseif (is_numeric(@$options['worksheetIndex'])) {
			$worksheet = $this->spreadsheet->setActiveSheetIndex($options['worksheetIndex']);
		} elseif (@$options['worksheetName']) {
			$worksheet = $this->spreadsheet->setActiveSheetIndexByName($options['worksheetName']);
		}

		if ($this->headerRow) {
			$this->headerNames = [];
			foreach ($worksheet->getRowIterator(1, 1) as $row) {
				$cellIterator = $row->getCellIterator();
				foreach ($cellIterator as $cell) {
					if ($cell !== null) {
						$value = trim($cell->getCalculatedValue());
						if ($value) {
							$this->headerNames[ $cell->getColumn() ] = $value;
						}
					}
				}
			}
		}

		foreach ($worksheet->getRowIterator( ($this->headerRow ? 2 : 1) ) as $row) {
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(true);
			foreach ($cellIterator as $cell) {
				if ($cell !== null) {
					$headerName = null;
					if ($this->headerRow) {
						$headerName = $this->headerNames[ $cell->getColumn() ];
					}

					if ($headerName) {
						$output[ $cell->getRow() ][ $headerName ] = trim($cell->getCalculatedValue());
					} else {
						$output[ $cell->getRow() ][ $this->columnToNumeric($cell->getColumn()) ] = trim($cell->getCalculatedValue());
					}
				}
			}
		}

		return $output;
	}

	public function dumpTable($options) {
		$data = $this->loadToArray( (!empty($options['loadToArrayOptions']) ? $options['loadToArrayOptions'] : []) );

		$output = '<table cellspacing="0" cellpadding="4" border="1">';

		if ($this->headerRow) {
			$output .= '<tr>';
			foreach ($this->headerNames as $headerName) {
				$output .= '<th>'. \yii\helpers\Html::encode($headerName) .'</th>';
			}
			$output .= '</tr>';
		}

		foreach ($data as $rowNumber => $row) {
			$output .= '<tr>';
			foreach ($row as $key => $cell) {
				$output .= '<td>'. \yii\helpers\Html::encode($cell) .'</td>';
			}
			$output .= '</tr>';
		}

		echo $output;
	}

	public function columnToNumeric($columnLetter) {
		return ord($columnLetter) - 64;  // A = 65
	}
}
