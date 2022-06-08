<?php

namespace App\Controller;

use App\Entity\Group;
use App\Repository\GroupRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GroupController extends AbstractController
{
    private const HTTP_STATUS_ERROR = 400;
    private const HTTP_STATUS_SUCCESS = 200;
    private const VALID_EXTENSION = ['xls', 'xlsx'];

    /**
     * @Route("/api/groups/import")
     */
    public function importAction(
        Request $request,
        GroupRepository $groupRepository
    ) {
        $file = $request->files->get('uploadFile');

        if (!$file || ($file instanceof UploadedFile === false)) {
            return new JsonResponse(
                ['error_msg' => 'Un fichier est requis.'],
                $this::HTTP_STATUS_ERROR
            );
        }

        if (!\in_array($file->getClientOriginalExtension(), $this::VALID_EXTENSION)) {
            return new JsonResponse(
                ['error_msg' => "L'extension " . $file->getClientOriginalExtension() . " n'est pas autorisée."],
                $this::HTTP_STATUS_ERROR
            );
        }

        $spreadsheet = IOFactory::load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $mandatoryColumns = [
            'Nom du groupe',
            'Origine',
            'Ville',
            'Année début',
        ];
        $columnsSetFunction = [
            'Nom du groupe' => 'setName',
            'Origine' => 'setCountry',
            'Ville' => 'setCity',
            'Année début' => 'setStartYear',
            'Année séparation' => 'setEndYear',
            'Fondateurs' => 'setFounders',
            'Membres' => 'setNumberOfMembers',
            'Courant musical' => 'setStyle',
            'Présentation' => 'setDescription',
        ];

        // Check the mandatory fields are provided
        $columns = $sheetData[1];
        $missing = \array_diff($mandatoryColumns, \array_values($sheetData[1]));
        if (\count($missing) > 0) {
            return new JsonResponse(
                [
                    'error_msg' => (\count($missing) > 1
                        ? 'Les colonnes suivantes sont obligatoires: '
                        : 'La colonne suivante est obligatoire: ') . implode(', ', $missing)
                ],
                $this::HTTP_STATUS_ERROR
            );
        }

        // Remove the first line which contains only the headers
        unset($sheetData[1]);

        // Build an array of Group entities from the data file
        $data = [];
        foreach ($sheetData as $row) {
            $groupEntity = new Group();

            \array_walk(
                $row,
                function($properties, $col) use (&$groupEntity, $columns, $columnsSetFunction) {
                    $groupEntity->{$columnsSetFunction[$columns[$col]]}($properties);
                }
            );

            $data[] = $groupEntity;
        }

        $nbGroupsImported = 0;
        foreach ($data as $groupEntity) {
            try {
                $groupRepository->upsert($groupEntity);
                $nbGroupsImported++;
            } catch (\Throwable $e) {
                return new JsonResponse(
                    ['error_msg' => $e->getMessage()],
                    $this::HTTP_STATUS_ERROR
                );
            }
        }

        return new JsonResponse(
            [
                'result' => 'Import succeed.',
                'number_of_groups_imported' => $nbGroupsImported,
            ],
            $this::HTTP_STATUS_SUCCESS
        );
    }
}
