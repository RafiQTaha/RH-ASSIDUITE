<?php

namespace App\Controller\Parametre;

use App\Controller\ApiController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('parametre/synchronisation')]
class SynchronisationController extends AbstractController
{
    private $em;
    private $api;
    private $api_RH;

    public function __construct(ManagerRegistry $doctrine, ApiController $api)
    {
        $this->em = $doctrine->getManager();
        $this->api = $api;
        $this->api_RH = HttpClient::create();
        ini_set('max_execution_time', 6000);
    }
    #[Route('/', name: 'parametre_Synchronisation', options: ['expose' => true])]
    public function parametre_Synchronisation(Request $request)
    {
        $operations = $this->api->check($this->getUser(), 'parametre_Synchronisation', $this->em, $request);
        if (!$operations) {
            return new AccessDeniedException();
        }
        return $this->render('parametre/synchronisation/synchronisation.html.twig', [
            'operations' => $operations,
        ]);
    }
    
    #[Route('/api_site', name: 'api_site', options: ['expose' => true])]
    public function api_site()
    {
        try {
            return $this->InsertOrUpdateMydatabase('site', 'site');
        } catch (\Throwable $th) {
            return new JsonResponse('Erreur de connection..!!', 500);
        }
    }
    #[Route('/api_employe', name: 'api_employe', options: ['expose' => true])]
    public function api_employe()
    {
        try {
            return $this->InsertOrUpdateMydatabase('employe', 'employe');
        } catch (\Throwable $th) {
            return new JsonResponse('Erreur de connection..!!', 500);
        }
    }
    #[Route('/api_contract', name: 'api_contract', options: ['expose' => true])]
    public function api_contract()
    {
        try {
            return $this->InsertOrUpdateMydatabase('contract', 'contract');
        } catch (\Throwable $th) {
            return new JsonResponse('Erreur de connection..!!', 500);
        }
    }

    public function InsertOrUpdateMydatabase($table, $link)
    {
        $from_id = 0;
        $responseRH = $this->api_RH->request('GET', $this->getParameter('api_RH') . $link . '/' . $from_id);
        $arraydata = $responseRH->toArray();
        $newRow = 0;
        $updatedRow = 0;
        foreach ($arraydata as $data) {
            $query = "select * from " . $table . " where id = " . $data['id'] . " limit 1";
            $current_row = $this->selectQuery($query);
            if ($current_row) {
                $commonKeys = array_intersect_key($data, $current_row);
                // dd($data, $current_row);
                $data = array_intersect_key($data, $commonKeys);
                $current_row = array_intersect_key($current_row, $commonKeys);
                $differences = array_diff_assoc($data, $current_row);
                if ($differences) {
                    $updated = $this->makeUpdateQuery($data, $table);
                    if ($updated) {
                        $updatedRow++;
                    }
                }
            } else {
                $inserted = $this->makeInsertQuery($data, $table);
                if ($inserted) {
                    $newRow++;
                }
            }
        }
        return new JsonResponse(['newRow' => $newRow, 'updated' => $updatedRow], 200);
    }

    function makeUpdateQuery($dataArray, $table)
    {
        // Assuming you have a common key to identify the rows (e.g., 'id')
        $commonKey = 'id';

        $commonKeyValue = $dataArray[$commonKey];

        // Build the SET part of the SQL query
        $setClause = [];
        foreach ($dataArray as $key => $value) {
            // Skip the common key
            if ($key !== $commonKey) {
                if ($value == null) {
                    $setClause[] = "`$key` = " . (is_numeric($value) ? "'".$value."'" : "null");
                } else {
                    $setClause[] = "`$key` = '".$value."'";
                }
            }
        }

        // Combine the SET clause into a string
        $setClauseString = implode(', ', $setClause);
        // Build the SQL query for the specific row
        $sql = "UPDATE $table SET $setClauseString WHERE $commonKey = $commonKeyValue";
        // dd($sql);
        $stmt = $this->em->getConnection()->prepare($sql);
        // dd('done');
        $stmt->executeQuery();
        return true;
    }
    function makeInsertQuery($data, $table)
    {
        $keys = array_keys($data);
        $values = array_values($data);

        $insertClause = [];
        // Quote string values and leave numeric values unquoted
        foreach ($values as $key => $value) {
            // $insertClause[] = "`$key` = " . (is_numeric($value) ? $value : '"'.$value.'"');
            // dd($values);
            if (!is_numeric($value)) {
                $value = $value == null ? "null" : '"' . $value . '"';
                $values[$key] = $value;
            }
        }
        // dd(implode(', ', $values));
        // Build the SQL query dynamically
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $keys),
            implode(', ', $values)
        );
        // dd($sql);
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->executeQuery();
        // dd('done');
        return true;
    }

    public function selectQuery($sqlRequest)
    {
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        return $stmt->executeQuery()->fetch();
        // return $stmt
    }
}
