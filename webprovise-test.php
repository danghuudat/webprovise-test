<?php

class Travel extends Base
{
    const API_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';

    private $companyPriceList = [];
    public function __construct() {
        parent::__construct(self::API_URL);
        $this->getCompanyPrice();
    }

    private function getCompanyPrice() :void
    {
        $companyPrices = [];
        foreach ($this->data as $key => $travel) {
            $companyPrices[$travel['companyId']] = isset($companyPrices[$travel['companyId']]) ? $companyPrices[$travel['companyId']] + floatval($travel['price']) : floatval($travel['price']);
        }
        $this->companyPriceList = $companyPrices;
    }

    public function getTotalCostByCompanyId($companyId) :float
    {
        return $this->companyPriceList[$companyId] ?? 0;
    }
}

class Company extends Base
{
    const API_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';

    private $travels;
    public function __construct() {
        parent::__construct(self::API_URL);
        $this->travels = new Travel();
    }

    private function createCompanyTree($companyList, $parentId = 0) :array
    {
        $trees = [];
        $companyCost = 0;
        $totalCost = 0;
        foreach ($companyList as $key => $company) {
            if ($company['parentId'] == $parentId) {
                $companyCost = 0;
                unset($companyList[$key]);
                list($children, $cost, $companyList) = $this->createCompanyTree($companyList, $company['id']);
                $companyCost = $this->travels->getTotalCostByCompanyId($company['id']) + $cost;
                $trees[] = [
                    'id' => $company['id'],
                    'name' => $company['name'],
                    'cost' => $companyCost,
                    'children' => $children,
                ];
                $totalCost += $companyCost;
            }
        }

        return [$trees, $totalCost, $companyList];
    }

    public function showTree() {
        list($trees) = $this->createCompanyTree($this->data);

        return $trees;
    }
}

class TestScript
{
    public function execute()
    {
        $startTime = microtime(true);
        $company = new Company();
        $companyTree = $company->showTree();
        echo json_encode($companyTree);
        echo 'Total time executed time: '.  (microtime(true) - $startTime);
    }
}

class Base {
    private $apiUrl;
    protected $data;

    public function __construct($apiUrl) {
        $this->apiUrl = $apiUrl;
        $this->data = $this->getData();
    }


    private function getData() :array
    {
        try {
            if (!$response = file_get_contents($this->apiUrl)) {
                echo 'Api '. get_called_class() .' error';
                return[];
            }

            return json_decode($response, true);

        } catch (Exception $e) {
            echo 'Error get data: ' . $e->getMessage();
            return [];
        }
    }
}

(new TestScript())->execute();
