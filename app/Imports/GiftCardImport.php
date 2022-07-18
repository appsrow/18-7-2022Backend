<?php

namespace App\Imports;

use App\GiftCard;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class GiftCardImport implements ToModel, WithHeadingRow, WithBatchInserts, WithValidation
{
    private $rows = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */

    public function model(array $row)
    {
        ++$this->rows;

        $rowExpiryDate = str_replace('/', '-', $row['expiry_date']);
        $expiryDate = Carbon::createFromFormat('d-m-Y', $rowExpiryDate)->format('Y-m-d');
        // $expiryDate = date('Y-m-d', strtotime($rowExpiryDate));
        return new GiftCard([
            "card_code" =>  Crypt::encryptString($row["card_code"]),
            "type" => $row["type"],
            "amount" => $row["amount"],
            "price" => $row["price"],
            "status" => "AVAILABLE",
            "redeemed_at" => NULL,
            "expiry_date" => $expiryDate
        ]);
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function batchSize(): int
    {
        return 1000;
    }
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'card_code' => 'required',
            "type" => 'required',
            "amount" => 'required',
            "price" => 'required',
            "expiry_date" => 'required|date_format:d/m/Y'
        ];
    }
}
