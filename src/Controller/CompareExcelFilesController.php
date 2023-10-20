<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompareExcelFilesController extends AbstractController
{
    #[Route('/excel_compare')]
    #[Route('/excel_compare/index')]
    public function index(Request $request): Response
    {
        return $this->render('upload_and_compare.html.twig');
    }

    public function compare(Request $request)
    {
        // Get the two excel files from the request.
        $file1 = $request->files->get('file1');
        $file2 = $request->files->get('file2');

        // Create two phpspreadsheet readers.
        $reader1 = IOFactory::load($file1->getPathname());
        $reader2 = IOFactory::load($file2->getPathname());

        // Get the worksheets from the two readers.
        $worksheet1 = $reader1->getActiveSheet();
        $worksheet2 = $reader2->getActiveSheet();

        // Create an empty array to store the comparison results.
        $results = [];

        // Iterate over the rows of the first worksheet.
        foreach ($worksheet1->getRowIterator() as $row) {

            // Get the current row number.
            $lineNumber = $row->getRowIndex();

            // Get the current row from the second worksheet.
            $currentRow = $worksheet2->getRowIterator($lineNumber)->current();

            // If the current row does not exist in the second worksheet, then the line is new.
            if ($currentRow === null) {
                $results[] = [
                    'line_number' => $lineNumber,
                    'status' => 'new',
                ];
            }

            // Otherwise, get the cell values for the current row in both worksheets.
            else {
                $cellValue1 = $worksheet1->toArray()[$lineNumber - 1][0];
                $cellValue2 = $worksheet2->toArray()[$lineNumber - 1][0];

                // If the cell values are equal, then the line is equal.
                if ($cellValue1 === $cellValue2) {
                    $results[] = [
                        'line_number' => $lineNumber,
                        'status' => 'equal',
                    ];
                }

                // Otherwise, the line is updated.
                else {
                    $results[] = [
                        'line_number' => $lineNumber,
                        'status' => 'updated',
                    ];
                }
            }
        }

        // Iterate over the remaining rows in the second worksheet.
        foreach ($worksheet2->getRowIterator() as $row) {

            // Get the current row number.
            $lineNumber = $row->getRowIndex();

            // If the current row number is greater than the number of rows in the first worksheet, then the line is new.
            if ($lineNumber > $worksheet1->getHighestRow()) {
                $results[] = [
                    'line_number' => $lineNumber,
                    'status' => 'new',
                ];
            }
        }

        // Return the comparison results.
        return $this->render('upload_and_compare.html.twig', [
            'response' => $results,
        ]);
    }
}
