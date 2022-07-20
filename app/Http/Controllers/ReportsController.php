<?php

namespace App\Http\Controllers;

use App\Models\Depent;
use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Mockery\Exception;
use \Yajra\DataTables\DataTables;

use App\Models\Associate;
use App\Models\Agreement;
use App\Models\Classification;


class ReportsController extends Controller
{

    public function __construct()
    {
      if (!Session::has('user')) {
        return redirect()->route('login');
      }
      if(!in_array(Session::get('typeId'),[1,2,3])){
        return redirect()->route('dashboard');
      }
    }
    /**
     * Class ReportsController
     * @param Request $request
     * @package App\Http\Controllers
     */

  public function associate(Request $request) {
    if (!Session::has('user')) {
        return redirect()->route('login');
    }
    if(!in_array(Session::get('typeId'),[1,2,3])){
      return redirect()->route('dashboard');
    }

    $agreementList = Agreement::orderBy('con_nome', 'asc')->get();
    $classificationList = Classification::orderBy('cla_nome', 'asc')->get();
    $referenceList = Agreement::distinct()->orderBy('con_referencia', 'asc')->get('con_referencia');

    $data = [
      'category_name' => 'reports',
      'page_name' => 'reports',
      'has_scrollspy' => 0,
      'scrollspy_offset' => '',
      'alt_menu' => 0,
      'agreementList' => $agreementList,
      'classificationList' => $classificationList,
      'referenceList' => $referenceList,
    ];

    return view('reports.associate')->with($data);
  }

  public function agreement(Request $request) {
    if (!Session::has('user')) {
        return redirect()->route('login');
    }
    if(!in_array(Session::get('typeId'),[1,2,3])){
      return redirect()->route('dashboard');
    }

    

  }

  public function covenant(Request $request) {
    // dd($request->all());
    
    $agreementList = Agreement::orderBy('con_nome', 'asc')->get();
    $classificationList = Classification::orderBy('cla_nome', 'asc')->get();
    $referenceList = Agreement::distinct()->orderBy('con_referencia', 'asc')->get('con_referencia');
    
    $data = [
      'category_name' => 'reports',
      'page_name' => 'reports',
      'has_scrollspy' => 0,
      'scrollspy_offset' => '',
      'alt_menu' => 0,
      'agreementList' => $agreementList,
      'classificationList' => $classificationList,
      'referenceList' => $referenceList,
    ];

    return view('reports.covenants')->with($data);
    
  }

  public function cashflow(Request $request) {
    if (!Session::has('user')) {
        return redirect()->route('login');
    }
    if(!in_array(Session::get('typeId'),[1,2,3])){
      return redirect()->route('dashboard');
    }

  }



  public function aReport(Request $request) {

    $pp = explode(' a ',$request->post('periodo'));
    $inicio = implode('-',array_reverse(explode('/',$pp[0])));
    $fim = implode('-',array_reverse(explode('/',$pp[1])));

    $retorno = [];

    switch($request->post('typeReport')){
      case "associate":
        /*
        */
        $cab1 = \DB::table('associado')->select('assoc_nome','assoc_matricula')->where("assoc_cpf", "=", $request->post('cpf'))->first();

        $retorno['cabecalho'] = "Associado: ".$cab1->assoc_nome."<br/>
          CPF: ".$request->post('cpf')."<br />
          Matrícula: ".$cab1->assoc_matricula."<br />
          Período: ".$request->post('periodo');

        $sqlBusca = "SELECT
                cv.con_nome,
                cl.cla_nome,
                p.par_vencimentoparcela,
                p.par_numero,
                p.par_equivalente,
                l.lanc_numerodeparcela,              
                l.lanc_contrato,
                p.par_valor,
                p.par_status
              FROM
                associado a,
                lancamento l,
                convenio cv,
                classificacao cl,
                parcelamento p
              WHERE
                a.assoc_cpf = '".$request->post('cpf')."'
                AND a.id = l.assoc_codigoid
                AND p.par_vencimentoparcela >= '".$inicio."'
                AND p.par_vencimentoparcela <= '".$fim."'
                AND l.con_codigoid = cv.id
                AND a.cla_codigoid = cl.id
                AND l.id = p.lanc_codigoid";

        if($request->post('convenio') != ''){
          $sqlBusca .= "AND cv.id = ".$request->post('convenio');
        }

        if($request->post('classificacao') != ''){
          $sqlBusca .= "AND cl.id = ".$request->post('classificacao');
        }

        if($request->post('referencia') != ''){
          $sqlBusca .= "AND cv.con_referencia = ".$request->post('convenio');
        }

        //echo $sqlBusca;
        $busca = \DB::select($sqlBusca);
        //dd($busca);
        if($busca){
          foreach($busca as $b){
            $retorno['tabela'][] = array(
              'convenio' => $b->con_nome,
              'classificacao' => $b->cla_nome,
              'vencimento' => $b->par_vencimentoparcela,
              'parcela' => $b->par_numero,
              'equivalencia' => $b->par_equivalente,
              'quantidade' => $b->lanc_numerodeparcela,
              'contrato' => $b->lanc_contrato,
              'valor' => number_format($b->par_valor,2),
              'status' => $b->par_status,
            );
          }
        } else {
          $retorno['erro'] = "Não existem resultados para esta busca";
        }

        break;
      case "agreement":
        break;
      case "covenant":
        
        $retorno['cabecalho'] = "Status de Pagamento";
        
        $sqlBusca = "SELECT
          a.assoc_nome,
          a.assoc_cpf,
          cv.con_nome,
          a.assoc_matricula,
          p.par_vencimentoparcela,
          p.par_numero,
          p.par_equivalente,
          l.lanc_numerodeparcela,              
          l.lanc_contrato,
          p.par_valor,
          p.par_status
        FROM
          associado a,
          lancamento l,
          convenio cv,
          classificacao cl,
          parcelamento p
        WHERE
          a.id = l.assoc_codigoid
          AND p.par_vencimentoparcela >= '".$inicio."'
          AND p.par_vencimentoparcela <= '".$fim."'
          AND l.con_codigoid = cv.id
          AND a.cla_codigoid = cl.id
          AND l.id = p.lanc_codigoid";

        if($request->post('convenio') != ''){
          $sqlBusca .= "AND cv.id = ".$request->post('convenio');
        }

        if($request->post('classificacao') != ''){
          $sqlBusca .= "AND cl.id = ".$request->post('classificacao');
        }

        if($request->post('referencia') != ''){
          $sqlBusca .= "AND cv.con_referencia = ".$request->post('convenio');
        }

        if($request->post('status') != ''){
          $sqlBusca .= "AND p.par_status = ".$request->post('status');
        }

        $busca = \DB::select($sqlBusca);
        if($busca){
          foreach($busca as $b){
          $retorno['tabela'][] = array(
            'nome' => $b->assoc_nome,
            'cpf' => $b->assoc_cpf,
            'convenio' => $b->con_nome,
            'matricula' => $b->assoc_matricula,
            'vencimento' => $b->par_vencimentoparcela,
            'parcela' => $b->par_numero,
            'equivalencia' => $b->par_equivalente,
            'quantidade' => $b->lanc_numerodeparcela,
            'contrato' => $b->lanc_contrato,
            'valor' => number_format($b->par_valor,2),
            'status' => $b->par_status,
          );
        }
      } else {
        $retorno['erro'] = "Não existem resultados para esta busca";
      }
        break;
      case "cashflow":
        break;
    }

    return json_encode($retorno);
  }




}