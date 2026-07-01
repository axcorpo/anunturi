<?php

namespace backend\modules\import\models;

ini_set('memory_limit',-1);
ini_set('max_execution_time', 0);

use common\models\Brand;
use common\models\ImportAlternativeSource;
use common\models\ImportColumn;
use common\models\ImportFile;
use common\models\ImportSheet;
use common\models\MarketingRecipient;
use common\models\Product;
use DateTime;
use DateTimeZone;
use Yii;
use yii\base\Model;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;
use yii\helpers\ArrayHelper;

class ImportForm extends Model
{
    /**
     * @var int The sheet id.
     */
    public $sheet_id;

    /**
     * @var array The SpreadsheetImport widget configuration.
     */
    public $spreadsheetImport;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sheet_id'], 'exist', 'targetClass' => ImportSheet::class, 'targetAttribute' => ['sheet_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sheet_id' => Yii::t('common', 'Sheet ID'),
        ];
    }

    /**
     * Gets the spreadsheet reader instance.
     *
     * @param string $fileType
     * @return \Box\Spout\Reader\ReaderInterface|\Box\Spout\Reader\XLSX\Reader|null
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     */
    protected static function getReader($file)
    {
        $fileType = mb_strtolower(end(explode('.', $file)));
        $reader = null;

        switch ($fileType) {
            case Type::XLSX:
                $reader = ReaderFactory::create(Type::XLSX);
                break;
            case Type::CSV:
                $reader = ReaderFactory::create(Type::CSV);
                $reader->setFieldDelimiter(ImportFile::detectDelimiter($file));
                $reader->setFieldEnclosure('"');
                $reader->setEndOfLineCharacter("\r");
                break;
            case Type::ODS:
                $reader = ReaderFactory::create(Type::ODS);
                break;
            default:
                $reader = ReaderFactory::create(Type::XLSX);
                break;
        }

        return $reader;
    }

    public function extractDateFormat($string)
    {
        $patterns = array(
            '/\b\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{3,8}Z\b/' => 'Y-m-d\TH:i:s.u\Z', // format DATE ISO 8601
            '/\b\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'Y-m-d',
            '/\b\d{4}-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])\b/' => 'Y-d-m',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-\d{4}\b/' => 'd-m-Y',
            '/\b(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-\d{4}\b/' => 'm-d-Y',

            '/\b\d{2}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{3,8}Z\b/' => 'Y-m-d\TH:i:s.u\Z', // format DATE ISO 8601
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-\d{2}\b/' => 'd-m-y',
            '/\b(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-\d{2}\b/' => 'm-d-y',
            '/\b\d{2}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'y-m-d',
            '/\b\d{2}-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])\b/' => 'y-d-m',

            '/\b\d{4}\/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\b/' => 'Y/d/m',
            '/\b\d{4}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'Y/m/d',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/\d{4}\b/' => 'd/m/Y',
            '/\b(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/\d{4}\b/' => 'm/d/Y',

            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/\d{2}\b/' => 'd/m/y',
            '/\b(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\/\d{2}\b/' => 'm/d/y',
            '/\b\d{2}\/(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\b/' => 'y/d/m',
            '/\b\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'y/m/d',

            '/\b\d{4}\.(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'Y.m.d',
            '/\b\d{4}\.(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\b/' => 'Y.d.m',
            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\.\d{4}\b/' => 'd.m.Y',
            '/\b(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\.\d{4}\b/' => 'm.d.Y',

            '/\b(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\.\d{2}\b/' => 'd.m.y',
            '/\b(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\.\d{2}\b/' => 'm.d.y',
            '/\b\d{2}\.(0[1-9]|1[0-2])\.(0[1-9]|[1-2][0-9]|3[0-1])\b/' => 'y.m.d',
            '/\b\d{2}\.(0[1-9]|[1-2][0-9]|3[0-1])\.(0[1-9]|1[0-2])\b/' => 'y.d.m',

            // for 24-hour | hours seconds
            '/\b(?:2[0-3]|[01][0-9]):[0-5][0-9](:[0-5][0-9])\.\d{3,6}\b/' => 'H:i:s.u',
            '/\b(?:2[0-3]|[01][0-9]):[0-5][0-9](:[0-5][0-9])\b/' => 'H:i:s',
            '/\b(?:2[0-3]|[01][0-9]):[0-5][0-9]\b/' => 'H:i',

            // for 12-hour | hours seconds
            '/\b(?:1[012]|0[0-9]):[0-5][0-9](:[0-5][0-9])\.\d{3,6}\b/' => 'h:i:s.u',
            '/\b(?:1[012]|0[0-9]):[0-5][0-9](:[0-5][0-9])\b/' => 'h:i:s',
            '/\b(?:1[012]|0[0-9]):[0-5][0-9]\b/' => 'h:i',

            '/\.\d{3}\b/' => '.v'
        );

        $format = preg_replace( array_keys( $patterns ), array_values( $patterns ), $string );
        return preg_match( '/\d/', $format ) ? '' : $format;
    }

    /**
     * Saves the model.
     *
     * @return bool
     * @throws \yii\db\Exception
     * @throws \Exception
     */
    public function save()
    {
        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            $sheet = ImportSheet::findOne([
                'id' => $this->sheet_id,
                'deleted' => ImportSheet::NO,
            ]);
            $sheetColumns = ImportColumn::findAll([
                'sheet_id' => $this->sheet_id,
                'deleted' => ImportColumn::NO,
            ]);

            $file = Yii::getAlias("@uploads/import/file/{$sheet->file->id}/{$sheet->file->file}");
            if (!is_file($file)) {
                throw new \Exception('The file does not exist.');
            }

            $reader = self::getReader($file);
            $reader->open($file);
            $recipients = [];
			$currentDate = date('Y-m-d H:i:s');
            foreach ($reader->getSheetIterator() as $spreadsheetIndex => $spreadsheet) {
                if ($spreadsheetIndex != $sheet->number) {
                    continue;
                }
                foreach ($spreadsheet->getRowIterator() as $rowIndex => $row) {

                    if ($rowIndex <= $sheet->header) {
                        continue;
                    }
                    $row = array_map('trim', $row);
                    if (empty(array_filter($row))) {
                        continue;
                    }
                    /** @var $model \yii\base\Model|\yii\db\ActiveRecord */
                    $model = new $this->spreadsheetImport['model'];
					$recipient = [];
                    foreach ($sheetColumns as $columnIndex => $sheetColumn) {
                        $recipient['status'] = MarketingRecipient::STATUS_ACTIVE;
                        $recipient['created_at'] = $currentDate;
                        $recipient['updated_at'] = $currentDate;
                    	switch ($sheetColumn->target) {
							default:$recipient[$sheetColumn->target] = $row[$sheetColumn->source_index]; break;
						}
					}
                    $recipients[] = $recipient;
                }
            }
            $reader->close();
			$counter = count($recipients);
			$data = [];
			for ($i = 0; $i < $counter; $i++) {
			    if (empty($recipients[$i]['name'])) {
                    $recipients[$i]['name'] = implode(' ', array_filter([
                        $recipients[$i]['last_name'],
                        $recipients[$i]['first_name'],
                    ]));
                }
				$data[] = array_values($recipients[$i]);
			}

            Yii::$app->getDb()
				->createCommand()
				->batchInsert(MarketingRecipient::tableName(), ['status', 'created_at', 'updated_at', 'first_name', 'last_name', 'name', 'email', 'phone'], $data)
				->execute();

            $transaction->commit();

            return true;
        } catch(\Exception $e) {
            return false;
        }
    }
}
