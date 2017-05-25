<?php
namespace Relatorios;

use Illuminate\Http\Response;
use Carbon\Carbon;

abstract class RelatoriosAbstract
{
	public static function descricao()
	{
		return static::$static_descricao;
	}

	public static function nome()
	{
		return static::$static_nome;
	}

	public static function listar()
	{
		return static::$static_listar;
	}

	public static function camposFormulario()
	{
		return static::$static_camposFormulario;
	}

	public static function permissoes()
	{
		return static::$static_permissoes;
	}

	public static function camposRelatorio()
	{
		return static::$static_camposRelatorio;
	}

	static public function criar($parametros)
	{
		return new static($parametros);
	}

	public function __construct($parametros)
	{
		$this->camposFormulario = static::camposFormulario();
		$this->descricao = static::descricao();
		$this->dados = null;
		$this->resultado = null;
		$this->camposRetornos = null;
		$this->parametros = $parametros;
		$this->validado = $this->validar();
	}

	abstract protected function construirQuery();

	public function executar()
	{
		$this->executado = true;
		if($this->validado)
		{
			$this->construirQuery();
			$this->dados = $this->query->get();
			return true;
		}
		else
		{
			return false;
		}
	}

	protected function validar()
	{
		$todosValidos = true;
		//Testar os parametros
		foreach($this->camposFormulario as $campo)
		{
			$retorno = $campo->valida($this->parametros[$campo->nome]);
			if(count($retorno['errors']) == 0)
			{
				$this->parametrosValidos[$campo->nome] = $retorno['value'];
			}
			else
			{
				$this->camposRetornos[$campo->nome]['errors'] = $retorno['errors'];
				$this->camposRetornos[$campo->nome]['label'] = $campo->label;
				$todosValidos = false;
			}
		}
		return $todosValidos;
	}

	public function get($modo)
	{
		if($this->executado and $this->validado)
		{
			$relatorio = ['campos' => static::camposRelatorio() , 'dados' => $this->dados, 'parametros' => $this->parametrosValidos];
			$this->$modo($relatorio);
			return $this;
		}
		else
		{
			return $this;
		}
	}

	protected function inArray($relatorio)
	{
		$dados = array_map(function($row){
			$row = (array)$row;
			return $row;
		},$relatorio['dados']);

		$this->resultado['inArray'] = $dadosInArray;
	}

	protected function onFront($relatorio)
	{
		$dados = array_map(function($row){
			return array_values(get_object_vars($row));
		},$relatorio['dados']);

		$this->resultado['onFront'] = ['dados' => $dados, 'campos' => $relatorio['campos']];
	}

	protected function inGrid($relatorio)
	{
		$columnDefs = array_map(function($columns){
			return ['headerName' => $columns['label'], 'field' => $columns['campo'], 'valueGetter' => $columns['formato'].'Getter(colDef,data)', 'enableRowGroup' => true, 'enablePivot' => true, 'enableValue' => true];
		},$relatorio['campos']);
		$this->resultado['inGrid'] = ['columnDefs' => $columnDefs, 'rowData' => $relatorio['dados']];
	}

	protected function inCsv($relatorio)
	{
		if(isset($relatorio['dados'][0]))
		{
			$campos = get_object_vars($relatorio['dados'][0]);
			array_walk($campos,function(&$keys,$columns){
				$keys = $columns;
			});
			$campos = array_values($campos);
		}
		else
		{
			$campos = array_map(function($columns){
				return $columns['campo'];
			},$relatorio['campos']);
		}

		$this->resultado['inCsv'] = ['campos'=> $campos,'dados'=>$relatorio['dados']];
	}

	protected function inPdf($relatorio)
	{
		$styleEven = true;
		$dados = array_map(function($row) use (&$styleEven) {
			$styleEven = (!$styleEven);
			$style = $styleEven ? 'evenRow' : 'oddRow';
			return array_map(function($secondRow) use ($style){
				return ['text' => $secondRow ? $secondRow : '', 'style' => $style];
			},array_values(get_object_vars($row)));
		},$relatorio['dados']);

		if(isset($relatorio['dados'][0]))
		{
			$campos = get_object_vars($relatorio['dados'][0]);
			array_walk($campos,function(&$keys,$columns){
				$keys = $columns;
			});
			$campos = array_values($campos);
		}
		else
		{
			$campos = array_map(function($row){
				return ['text' => $row['label'], 'style' => 'header'];
			},$relatorio['campos']);
		}

		$parametros = array_map(function($row) use ($relatorio){
			if(sizeof($row->params) > 0 && is_array($relatorio['parametros'][$row->nome])){
				$text = $row->label;
				foreach($row->params['selectionOptions'] as $item){
					if(in_array($item['value'], $relatorio['parametros'][$row->nome])){
						$text .= $text == $row->label ? ' - '. $item['option'] : ' / '. $item['option'];
					}
				}
			}else{
				$text = $relatorio['parametros'][$row->nome] != null ? $row->label.' - '. $relatorio['parametros'][$row->nome] : '';
			}
			return ['text' => $text, 'style' => 'header'];
		}, $this->camposFormulario);

		$this->resultado['inPdf'] = ['dados' => $dados, 'campos' => $campos, 'parametros' => $parametros];
	}

	public function resultado()
	{
		//Retorna o resultado;
		return $this->resultado;
	}

	public function camposRetornos()
	{
		//Retorna os retornos dos campos
		return $this->camposRetornos;
	}

}