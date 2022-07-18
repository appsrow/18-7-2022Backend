<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class CampaignReport implements FromCollection, WithHeadings
{
    protected $data;
    protected $locale;

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function __construct($data)
    {
        $this->locale = app()->getLocale();
        $this->data = $data;
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function headings(): array
    {
        if ($this->locale === 'es') {
            return [
                "ID de campaña",
                "ID de la compañía",
                "Nombre de campaña",
                "Categoría",
                "Nombre de la marca",
                "Fecha de inicio",
                "Fecha final",
                "Estado de la campaña",
                "Usuarios dirigidos",
                "Financiamiento de campañas",
                "Usuarios completados",
                "Déficit",
                "Fondo asignado",
                "Fondo de crédito",
                "Beneficio estimado"
            ];
        } else {
            return [
                "CampaignId",
                "CompanyId",
                "CampaignName",
                "Type",
                "BrandName",
                "StartDate",
                "EndDate",
                "CampaignStatus",
                "TargetedUsers",
                "CampaignFunding",
                "CompletedUsers",
                "ShortFall",
                "AllocatedFund",
                "CreditFund",
                "EstimatedProfit"
            ];
        }
    }
}
