<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\Associate;

use App\Models\Competence;
use App\Models\Convenant;
use App\Models\Portion;

use App\Models\Typeassociate;
use App\Models\Classification;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Mockery\Exception;
use function PHPUnit\Framework\isEmpty;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 *
 */
class ConvenantController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        if (!Session::has('user')) {
            return redirect()->route('login');
        }

        $associateList = Associate::orderBy('assoc_nome','asc')->get();
        $competitionList = Competence::all();
        $agreementList = Agreement::orderBy('con_nome', 'asc')->get();


        $data = [
            'category_name' => 'covenants',
            'page_name' => 'covenants',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];

        $lists = [
            'associateList'=> $associateList,
            'competitionList'=> $competitionList,
            'agreementList' => $agreementList,
        ];

        return view('covenants.list', $lists)->with($data);
    }

    public function uploadFile(Request $request)
    {

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function getCovenants(Request $request)
    {
        if($request->ajax()){

            $dynamicWhere = [];

            if($request->post('selAssociate')){
                $dynamicWhere[] = ['lancamento.assoc_codigoid', '=', $request->selAssociate];
            }

            if($request->post('selAgreement')){
                $dynamicWhere[] = ['lancamento.con_codigoid','=', $request->selAgreement];
            }

            try {
                //load Convenants from table lancamentos
                $convenantList = Convenant::select('*', 'lancamento.id AS lanc_codigoid')
                    ->join('associado', 'associado.id', '=', 'lancamento.assoc_codigoid')
                    ->join('convenio', 'convenio.id', '=', 'lancamento.con_codigoid')
                    ->where($dynamicWhere)
                    ->get();

                foreach ($convenantList as $index => $item) {
                    //load portion within lanc_codigoid iqual to id from lancamento
                    $convenantList[$index]['portion'] = Portion::select('*', 'parcelamento.id AS par_codigoid')
                        ->join('competencia', 'competencia.id', '=', 'parcelamento.com_codigoid')
                        ->where('parcelamento.lanc_codigoid', $item->lanc_codigoid)
                        ->get();
                }

                return response()->json($convenantList);
            }catch (Exception $e){
                return response()->json(['status'=>'error', 'msg'=> $e->getMessage()]);
            }
        }

    }

    /**
     * @param $id
     * @param string $status
     * @return mixed
     */
    protected function changeStatusPortion($id, $status){
        try{
            // load portion
            $portion = Portion::where('id', $id)
                ->update(
                    [
                        'par_status' => $status
                    ]
                );

            return $portion;
        } catch (Exception $e) {
            return response()->json(['status'=>'error', 'msg'=> $e->getMessage()]);
        }

    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function changePayment(Request $request){
        $arrayId = $request->id;

        foreach ($arrayId as $id){
            try{

                $affected = self::changeStatusPortion($id, 'Pago');

                if($affected > 0){
                    return response()->json(['status'=>'success', 'msg'=> 'Parcela Quitada com sucesso!']);
                }
            }catch (Exception $e){
                return response()->json(['status'=>'error', 'msg'=> $e->getMessage()]);
            }
        }

    }

    /**
     * @param $compentence
     * @return mixed
     */
    protected function verifyCompetent($compentence)
    {
        $competenceList = Competence::where(['com_nome'=>$compentence])->get();

        if(isEmpty($competenceList)){

            $competenceModel = new Competence();

            $newData = explode('/', $compentence);

            $beginMouth = $newData[0] - 1;
            $beginYear = $newData[1];

            if($beginMouth === 0 ){
                $beginMouth = 12;
                $beginYear = $beginYear - 1;
            }

            try {
                $competenceModel->com_nome = $compentence;
                $competenceModel->com_datainicio = $beginYear.'-'. $beginMouth .'-11';
                $competenceModel->com_datafinal = $newData[1].'-'.$newData[0].'-10';
                $competenceModel->save();

                return Competence::where(['com_nome'=>$compentence])->get();
            }catch (Exception $exception){
                return response()->json(['status'=>'error', 'msg'=> $e->getMessage()]);
            }

        }


    }


    public function getMonthlyPayment()
    {
        $dataConvenants = Convenant::where('con_codigoid', '=', 31)->count();

        $data = [
            'data' => $dataConvenants,
        ];

        return response()->json($data,'200');
    }

    public function storeMonthlyPayment(Request $request)
    {

        $row = 1;

        if($request->file('file')->getClientOriginalExtension() !== "csv"){
            return response()->json(['status'=>'error', 'message'=>'Arquivo inválido']);
        }

        if (($handle = fopen($request->file('file'), "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                if($row != 1){
                    foreach ($data as $content){

                       $contentExploded = explode(";", $content);

                       $dataAssociate = Associate::where('assoc_cpf', $contentExploded[4])->get();

                       if(empty($dataAssociate[0])){

                           $tipoAssocModel = Typeassociate::select('*')
                               ->where('tipassoc_nome','=', $contentExploded[8])
                               ->get();
                           $contentExploded[8] = $tipoAssocModel[0]->id;


                           $classificationModel = Classification::select('*')
                               ->where('cla_nome', '=',$contentExploded[10])
                               ->get();
                           $contentExploded[10] = $classificationModel[0]->id;

                           $dateBirthday =  explode('/', $contentExploded[3]);

                           $dateBirthdayFormated = implode('-', array_reverse($dateBirthday));

                           $formDataExplode = explode('/', $contentExploded[19]);

                           $dataFormated = implode('-', array_reverse($formDataExplode));

                           $associateModel = new Associate();

                           $associateModel->assoc_nome = $contentExploded[0];
                           $associateModel->assoc_identificacao = $contentExploded[1];
                           $associateModel->assoc_matricula = $contentExploded[2];
                           $associateModel->assoc_datanascimento = $dateBirthdayFormated;
                           $associateModel->assoc_cpf = $contentExploded[4];
                           $associateModel->assoc_rg = $contentExploded[5];
                           $associateModel->assoc_sexo = $contentExploded[6];
                           $associateModel->assoc_profissao = $contentExploded[7];
                           $associateModel->tipassoc_codigoid = $contentExploded[8];
                           $associateModel->assoc_email = $contentExploded[9];
                           $associateModel->cla_codigoid = $contentExploded[10];
                           $associateModel->assoc_estadocivil = $contentExploded[11];
                           $associateModel->assoc_fone = $contentExploded[12];
                           $associateModel->assoc_agencia = $contentExploded[13];
                           $associateModel->assoc_cep = $contentExploded[14];
                           $associateModel->assoc_endereco = $contentExploded[15];
                           $associateModel->assoc_bairro = $contentExploded[16];
                           $associateModel->assoc_uf = $contentExploded[17];
                           $associateModel->assoc_cidade = $contentExploded[18];
                           $associateModel->assoc_dataativacao = $dataFormated;
                           $associateModel->assoc_contrato = $contentExploded[20];

                           $associateModel->save();


                       }//end IF empty($dataAssociate[0])


                    }//End Foreach($data as $content)

                }//end IF($row != 1)

                $row++;

            }//End While

            $responseData = [
                'status' => 'success',
                'msg' => 'Arquivo processado com sucesso!',
            ];
        }else{
            $responseData = [
                'status' => 'error',
                'msg' => 'Arquivo não pode ser processado!',
            ];
        }// End IF !== FALSE

        fclose($handle);

        return response()->json([$responseData], 200);
    }


    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function renegotiation($portion_id, $convenants_id)
    {
        try {
            //Change Status Portion to renegociation
            self::changeStatusPortion($portion_id, 'Transferido');
            //Load Portion
            $portion = Portion::select('*', 'competencia.id AS com_codigoid')
                ->join('competencia', 'competencia.id', '=', 'parcelamento.com_codigoid')
                ->where('lanc_codigoid', $convenants_id)
                ->latest('par_numero')
                ->first();

            //Load Convenants with portion
            $convenants = Convenant::where('id', $portion['lanc_codigoid'])->get();

            try {

                $newCompetenceExploded = explode('/', $portion['com_nome']);
                //Increment 1 mount
                $newCompetenceExploded[0] = $newCompetenceExploded[0] + 1;

                if($newCompetenceExploded[0] > 12){
                    $newCompetenceExploded[0] = $newCompetenceExploded[0] - 12;
                    $newCompetenceExploded[1] = $newCompetenceExploded[1] + 1;
                }

                $newCompetenceExploded[0] = str_pad($newCompetenceExploded[0], 2, '0', STR_PAD_LEFT);

                /**
                 * Verify compentece exists
                 */

                $newCompetence = self::verifyCompetent($newCompetenceExploded[0].'/'.$newCompetenceExploded[1]);

                /**
                 * Select Portion have a Lancamento ID
                 * Insert a new Portion
                 */

                $modelPortion = new Portion();

                $modelPortion->par_valor       = $portion["par_valor"];
                $modelPortion->lanc_codigoid   = $convenants_id;
                $modelPortion->par_numero      = $portion["par_numero"] + 1;
                $modelPortion->par_equivalente = $portion["par_numero"] + 1;
                $modelPortion->com_codigoid    = $newCompetence[0]->id;
                $modelPortion->par_status      = 'Pendente';

                $modelPortion->save();

                try {
                    $day = date('d');

                    //Change Due Date from Lancamento table
                    Convenant::where('id', $portion['lanc_codigoid'])
                        ->update([
                            'lanc_datavencimento' =>$newCompetenceExploded[1].'-'. $newCompetenceExploded[0] .'-'. $day,
                            'lanc_numerodeparcela' => $convenants[0]->lanc_numerodeparcela + 1
                        ]);

                    return response()->json(['status'=>'success', 'msg'=>'Parcela renegociada com sucesso!']);
                }catch (Exception $e){
                    return response()->json(['status'=>'error', 'msg'=> $e->getMessage()]);
                }

            }catch (Exception $e){
                return response()->json(['status'=>'error', 'msg'=> $e->getMessage()]);
            }

        }catch (Exception $e){
            return response()->json(['status'=>'error', 'msg' => $e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $convenantModel = new Convenant();

        try {
            //create Convenants
            $convenantModel->lanc_valortotal = str_replace(',','.', $request->total);
            $convenantModel->lanc_numerodeparcela = $request->number;
            $convenantModel->con_codigoid = $request->convenants;
            $convenantModel->lanc_datavencimento = date('Y-m-d', strtotime($request->duedate));;
            $convenantModel->assoc_codigoid = $request->associate;
            $convenantModel->est_codigoid = 2;

            $convenantModel->save();
            $lasInsertIdConvenat = Convenant::latest('id')->first();

            try {

                $currentMonth = explode("-", date('Y-m-d'));

                $monthUpdated = intval($currentMonth[1]);
                $yearUpdated  = intval($currentMonth[0]);

                //create portion
                for ($i = 1; $request->number >= $i; $i++) {

                    $monthUpdated++;

                    if ($monthUpdated > 12) {
                        $monthUpdated = 01;
                        $yearUpdated++;
                    }

                    if ($monthUpdated < 10) {
                        $monthUpdated = "0".$monthUpdated;
                    }

                    $competenceID = Competence::where('com_nome', '=', $monthUpdated.'/'.$yearUpdated)->get();

                    if((int)$competenceID[0]['id'] > 0 ){
                        $portionModel = new Portion();

                        $portionModel->par_numero = $i;
                        $portionModel->par_valor = str_replace(',','.',$request->portion);
                        $portionModel->lanc_codigoid = $lasInsertIdConvenat['id'];
                        $portionModel->par_vencimentoparcela = $yearUpdated.'-'.$monthUpdated.'-10';
                        $portionModel->par_observacao = '';
                        $portionModel->par_status = 'Pendente';
                        $portionModel->com_codigoid = $competenceID[0]['id'];
                        $portionModel->par_equivalente = $i;
                        $portionModel->par_habilitasn = 0;

                        $portionModel->save();


                    }


                }
                return response()->json(['status'=>'success', 'msg'=> 'Formulário salvo com sucesso']);
            }catch (Exception $e){
                return response()->json(['status'=>'error', 'msg'=> $e->getMessage()]);
            }

        }catch (Exception $e){
            return response()->json(['status'=>'error', 'msg'=> $e->getMessage()]);
        }

    }
}
