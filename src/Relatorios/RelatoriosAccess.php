<?php
namespace Relatorios;

abstract class RelatoriosAccess
{
	protected $relatorios = [];

	//Separa os relatorios que podem ser vistos pelo usuário
	public function getRelatoriosForUserPermissions($permissoes)
	{
		$relatorios = $this->getRelatorios();

		foreach($relatorios as $index => $relatorio)
		{
			$todasPermissoesChecadas = true;
			foreach($relatorio['permissoes'] as $permissao)
			{
				$todasPermissoesChecadas = (($todasPermissoesChecadas and in_array($permissao,$permissoes)) or (in_array("relatorios.all",$permissoes)));
			}
			if(!$todasPermissoesChecadas)
			{
				unset($relatorios[$index]);
			}
		}
		return $relatorios;
	}


	public function executarRelatorio($params)
	{
		$requestRelatorio = $this->prepararEntradaRelatorio($params['campos']);
		$relatorio = $params['namespace']::criar($requestRelatorio);
		$relatorio->executar();

		$resultado = $relatorio->get('inGrid')->get('inPdf')->get('inCsv')->resultado();
		$resultado['retornos'] = $relatorio->camposRetornos();
		$resultado['validado'] = $relatorio->validado;

		return $resultado;
	}

	protected function prepararEntradaRelatorio($campos)
	{
		$entrada = [];
		foreach($campos as $campo)
		{
			if(isset($campo['resposta'])){
				$entrada[$campo['nome']] = $campo['resposta'];
			}
			else
			{
				$entrada[$campo['nome']] = null;
			}
		}
		return $entrada;
	}

	//Devolve o relatorio selecionado com os campos de formulário
	public function formularioRelatorio($relatorio)
	{
		if(class_exists($relatorio['namespace']))
		{
			$retorno = [
				'nome' => $relatorio['namespace']::nome(),
				'namespace' => $relatorio['namespace'],
				'descricao' => $relatorio['namespace']::descricao(),
				'permissoes' => $relatorio['namespace']::permissoes(),
				'campos' => $relatorio['namespace']::camposFormulario(),
			];
		}
		else
		{
			$retorno = null;
		}
		return $retorno;
	}

	//Separa os relatorios dos arquivos com as respectivas informações
	public function getRelatorios()
	{
		$relatorios = [];
		foreach($this->relatorios as $relatorio)
		{
			$relatorios[] = [
				'nome' => $relatorio::nome(),
				'namespace' => $relatorio,
				'descricao' => $relatorio::descricao(),
				'permissoes' => $relatorio::permissoes(),
			];
		}
		return $relatorios;
	}
}