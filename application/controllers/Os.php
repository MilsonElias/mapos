<?php

if (!defined("BASEPATH")) {
    exit("No direct script access allowed");
}

class Os extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper("form");
        $this->load->model("os_model");
        $this->data["menuOs"] = "OS";
    }

    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        $this->load->model("mapos_model");
        $this->data["custom_error"] = "";

        // --- PASSO 1: RECONSTRUIR O ARRAY DE FILTROS ---
        $where_array = [];
        $pesquisa = $this->input->get("pesquisa");
        $status = $this->input->get("status");
        $inputDe = $this->input->get("data");
        $inputAte = $this->input->get("data2");
        $faixa_preco = $this->input->get("faixa_preco");
        $ordenar = $this->input->get("ordenar");

        if ($pesquisa) {
            $where_array["pesquisa"] = $pesquisa;
        }
        if ($status) {
            $where_array["status"] = $status;
        }
        if ($inputDe) {
            $de = explode("/", $inputDe);
            if (count($de) == 3) {
                $where_array["de"] = $de[2] . "-" . $de[1] . "-" . $de[0];
            }
        }
        if ($inputAte) {
            $ate = explode("/", $inputAte);
            if (count($ate) == 3) {
                $where_array["ate"] = $ate[2] . "-" . $ate[1] . "-" . $ate[0];
            }
        }
        if ($faixa_preco) {
            $where_array["faixa_preco"] = $faixa_preco;
        }
        if ($ordenar) {
            $where_array["ordenar"] = $ordenar;
        }

        // --- PASSO 2: FAZER A BUSCA NO BANCO USANDO OS FILTROS ---
        $listaCompleta = $this->os_model->getOs(
            "os",
            'os.*, clientes.idClientes, clientes.nomeCliente, clientes.email, clientes.celular as celular_cliente, usuarios.nome,
        COALESCE((SELECT SUM(produtos_os.preco * produtos_os.quantidade) FROM produtos_os WHERE produtos_os.os_id = os.idOs), 0) as totalProdutos,
        COALESCE((SELECT SUM(servicos_os.preco * servicos_os.quantidade) FROM servicos_os WHERE servicos_os.os_id = os.idOs), 0) as totalServicos',
            $where_array, // Usando o array de filtros aqui
            0,
            0
        );

        // --- PASSO 3: SEPARAR OS RESULTADOS PARA KANBAN, MODAL E LISTA ---
        $statusKanban = ["Aberto", "Em Andamento", "Aguardando Peças", "Finalizado"];
        $statusMap = [
            "Aberto" => "pendente",
            "Em Andamento" => "em-processo",
            "Aguardando Peças" => "aguardando",
            "Finalizado" => "concluido",
        ];

        $ossNoKanban = [];
        $os_disponiveis = [];

        foreach ($listaCompleta as $os) {
            if (in_array(trim($os->status), $statusKanban)) {
                $ossNoKanban[] = $os;
            } else {
                $os_disponiveis[] = $os;
            }
        }

        $tasks_for_js = [];
        foreach ($ossNoKanban as $os) {
            setlocale(LC_TIME, "pt_BR", "pt_BR.utf-8", "portuguese");
            $tasks_for_js[] = [
                "id" => (int) $os->idOs,
                "title" => "#: " . $os->idOs . "<br>" . $os->nomeCliente,
                "responsavel" => $os->nome,
                "description" => $os->descricaoProduto ?: "Sem descrição de produto.",
                "defecty" => $os->defeito ?: "Sem descrição de defeito.",
                "status" => $statusMap[$os->status] ?? "pendente",
                "date" => $os->dataFinal ? strftime("%d %b, %y", strtotime($os->dataFinal)) : "",
                "editavel" => $this->os_model->isEditable($os->idOs),
                "faturado" => (int) ($os->status === "Faturado" || (int) $os->faturado === 1),
            ];
        }
        $this->data["tasks_json"] = json_encode($tasks_for_js);

        $osd_map = [];
        foreach ($os_disponiveis as $osd) {
            $osd_map[$osd->idOs] = $osd;
        }
        $this->data["os_disponiveis_json"] = json_encode($osd_map);
        $this->data["os_disponiveis"] = $os_disponiveis;

        // A variável $listaCompleta já contém os dados filtrados para a Tabela e Cards
        $this->data["results"] = $listaCompleta;
        $this->data["stats"] = $this->os_model->countByStatus();

        // --- PASSO 4: CARREGAR A VIEW ---
        $this->data["view"] = "os/os";
        return $this->layout();
    }

    public function adicionar()
    {
        if (!$this->permission->checkPermission($this->session->userdata("permissao"), "aOs")) {
            $this->session->set_flashdata("error", "Você não tem permissão para adicionar O.S.");
            redirect(base_url());
        }

        $this->load->library("form_validation");
        $this->data["custom_error"] = "";

        if ($this->form_validation->run("os") == false) {
            $this->data["custom_error"] = validation_errors() ? true : false;
        } else {
            $dataInicial = $this->input->post("dataInicial");
            $dataFinal = $this->input->post("dataFinal");
            $termoGarantiaId = $this->input->post("termoGarantia");

            try {
                $dataInicial = explode("/", $dataInicial);
                $dataInicial = $dataInicial[2] . "-" . $dataInicial[1] . "-" . $dataInicial[0];

                if ($dataFinal) {
                    $dataFinal = explode("/", $dataFinal);
                    $dataFinal = $dataFinal[2] . "-" . $dataFinal[1] . "-" . $dataFinal[0];
                } else {
                    $dataFinal = date("Y/m/d");
                }

                $termoGarantiaId = !$termoGarantiaId == null || !$termoGarantiaId == "" ? $this->input->post("garantias_id") : null;
            } catch (Exception $e) {
                $dataInicial = date("Y/m/d");
                $dataFinal = date("Y/m/d");
            }

            $data = [
                "dataInicial" => $dataInicial,
                "clientes_id" => $this->input->post("clientes_id"), //set_value('idCliente'),
                "usuarios_id" => $this->input->post("usuarios_id"), //set_value('idUsuario'),
                "dataFinal" => $dataFinal,
                "garantia" => set_value("garantia"),
                "garantias_id" => $termoGarantiaId,
                "descricaoProduto" => $this->input->post("descricaoProduto"),
                "defeito" => $this->input->post("defeito"),
                "status" => set_value("status"),
                "observacoes" => $this->input->post("observacoes"),
                "laudoTecnico" => $this->input->post("laudoTecnico"),
                "faturado" => 0,
            ];

            if (is_numeric($id = $this->os_model->add("os", $data, true))) {
                $this->load->model("mapos_model");
                $this->data["custom_error"] = "";
                $this->load->model("usuarios_model");

                $idOs = $id;
                $os = $this->os_model->getById($idOs);
                $emitente = $this->mapos_model->getEmitente();

                $tecnico = $this->usuarios_model->getById($os->usuarios_id);

                // Verificar configuração de notificação
                if ($this->data["configuration"]["os_notification"] != "nenhum" && $this->data["configuration"]["email_automatico"] == 1) {
                    $remetentes = [];
                    switch ($this->data["configuration"]["os_notification"]) {
                        case "todos":
                            array_push($remetentes, $os->email);
                            array_push($remetentes, $tecnico->email);
                            array_push($remetentes, $emitente->email);
                            break;
                        case "cliente":
                            array_push($remetentes, $os->email);
                            break;
                        case "tecnico":
                            array_push($remetentes, $tecnico->email);
                            break;
                        case "emitente":
                            array_push($remetentes, $emitente->email);
                            break;
                        default:
                            array_push($remetentes, $os->email);
                            break;
                    }
                    $this->enviarOsPorEmail($idOs, $remetentes, "Ordem de Serviço - Criada");
                }

                $this->session->set_flashdata(
                    "success",
                    "OS adicionada com sucesso, você pode adicionar produtos ou serviços a essa OS nas abas de Produtos e Serviços!"
                );
                log_info("Adicionou uma OS. ID: " . $id);
                redirect(site_url("os/editar/") . $id);
            } else {
                $this->data["custom_error"] = '<div class="alert">Ocorreu um erro.</div>';
            }
        }

        $this->data["view"] = "os/adicionarOs";

        return $this->layout();
    }

    public function editar()
    {
        if (!$this->uri->segment(3) || !is_numeric($this->uri->segment(3)) || !$this->os_model->getById($this->uri->segment(3))) {
            $this->session->set_flashdata("error", "OS não encontrada ou parâmetro inválido.");
            redirect("os/gerenciar");
        }

        if (!$this->permission->checkPermission($this->session->userdata("permissao"), "eOs")) {
            $this->session->set_flashdata("error", "Você não tem permissão para editar O.S.");
            redirect(base_url());
        }

        $this->load->library("form_validation");
        $this->data["custom_error"] = "";
        $this->data["texto_de_notificacao"] = $this->data["configuration"]["notifica_whats"];

        $idOsEditar = $this->input->post("idOs") ?: $this->uri->segment(3);
        $this->data["editavel"] = $this->os_model->isEditable($idOsEditar);
        if (!$this->data["editavel"]) {
            $this->session->set_flashdata(
                "error",
                "Esta OS já foi faturada/cancelada e seu status não pode ser alterado nem suas informações atualizadas. Por favor abrir uma nova OS ou gerar retorno."
            );

            redirect(site_url("os"));
        }

        if ($this->form_validation->run("os") == false) {
            $this->data["custom_error"] = validation_errors() ? '<div class="form_error">' . validation_errors() . "</div>" : false;
        } else {
            $dataInicial = $this->input->post("dataInicial");
            $dataFinal = $this->input->post("dataFinal");
            $termoGarantiaId = $this->input->post("garantias_id") ?: null;

            try {
                $dataInicial = explode("/", $dataInicial);
                $dataInicial = $dataInicial[2] . "-" . $dataInicial[1] . "-" . $dataInicial[0];

                $dataFinal = explode("/", $dataFinal);
                $dataFinal = $dataFinal[2] . "-" . $dataFinal[1] . "-" . $dataFinal[0];
            } catch (Exception $e) {
                $dataInicial = date("y/m/d");
            }

            $data = [
                "dataInicial" => $dataInicial,
                "dataFinal" => $dataFinal,
                "garantia" => $this->input->post("garantia"),
                "garantias_id" => $termoGarantiaId,
                "descricaoProduto" => $this->input->post("descricaoProduto"),
                "defeito" => $this->input->post("defeito"),
                "status" => $this->input->post("status"),
                "observacoes" => $this->input->post("observacoes"),
                "laudoTecnico" => $this->input->post("laudoTecnico"),
                "usuarios_id" => $this->input->post("usuarios_id"),
                "clientes_id" => $this->input->post("clientes_id"),
            ];
            $os = $this->os_model->getById($this->input->post("idOs"));

            //Verifica para poder fazer a devolução do produto para o estoque caso OS seja cancelada.

            if (strtolower($this->input->post("status")) == "cancelado" && strtolower($os->status) != "cancelado") {
                $this->devolucaoEstoque($this->input->post("idOs"));
            }

            if (strtolower($os->status) == "cancelado" && strtolower($this->input->post("status")) != "cancelado") {
                $this->debitarEstoque($this->input->post("idOs"));
            }

            if ($this->os_model->edit("os", $data, "idOs", $this->input->post("idOs")) == true) {
                $this->load->model("mapos_model");
                $this->load->model("usuarios_model");

                $idOs = $this->input->post("idOs");

                $os = $this->os_model->getById($idOs);
                $emitente = $this->mapos_model->getEmitente();
                $tecnico = $this->usuarios_model->getById($os->usuarios_id);

                // Verificar configuração de notificação
                if ($this->data["configuration"]["os_notification"] != "nenhum" && $this->data["configuration"]["email_automatico"] == 1) {
                    $remetentes = [];
                    switch ($this->data["configuration"]["os_notification"]) {
                        case "todos":
                            array_push($remetentes, $os->email);
                            array_push($remetentes, $tecnico->email);
                            array_push($remetentes, $emitente->email);
                            break;
                        case "cliente":
                            array_push($remetentes, $os->email);
                            break;
                        case "tecnico":
                            array_push($remetentes, $tecnico->email);
                            break;
                        case "emitente":
                            array_push($remetentes, $emitente->email);
                            break;
                        default:
                            array_push($remetentes, $os->email);
                            break;
                    }
                    $this->enviarOsPorEmail($idOs, $remetentes, "Ordem de Serviço - Editada");
                }

                $this->session->set_flashdata("success", "Os editada com sucesso!");
                log_info("Alterou uma OS. ID: " . $this->input->post("idOs"));
                redirect(site_url("os/editar/") . $this->input->post("idOs"));
            } else {
                $this->data["custom_error"] = '<div class="form_error"><p>Ocorreu um erro</p></div>';
            }
        }

        $this->data["result"] = $this->os_model->getById($this->uri->segment(3));

        $this->data["produtos"] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data["servicos"] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data["anexos"] = $this->os_model->getAnexos($this->uri->segment(3));
        $this->data["anotacoes"] = $this->os_model->getAnotacoes($this->uri->segment(3));

        if ($return = $this->os_model->valorTotalOS($this->uri->segment(3))) {
            $this->data["totalServico"] = $return["totalServico"];
            $this->data["totalProdutos"] = $return["totalProdutos"];
        }

        $this->load->model("mapos_model");
        $this->data["emitente"] = $this->mapos_model->getEmitente();

        $this->data["view"] = "os/editarOs";

        return $this->layout();
    }

    public function visualizar()
    {
        if (!$this->uri->segment(3) || !is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata("error", "Item não pode ser encontrado, parâmetro não foi passado corretamente.");
            redirect("mapos");
        }

        if (!$this->permission->checkPermission($this->session->userdata("permissao"), "vOs")) {
            $this->session->set_flashdata("error", "Você não tem permissão para visualizar O.S.");
            redirect(base_url());
        }

        $this->data["custom_error"] = "";
        $this->data["texto_de_notificacao"] = $this->data["configuration"]["notifica_whats"];

        $this->load->model("mapos_model");
        $this->data["result"] = $this->os_model->getById($this->uri->segment(3));
        $this->data["produtos"] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data["servicos"] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data["emitente"] = $this->mapos_model->getEmitente();
        $this->data["anexos"] = $this->os_model->getAnexos($this->uri->segment(3));
        $this->data["anotacoes"] = $this->os_model->getAnotacoes($this->uri->segment(3));
        $this->data["editavel"] = $this->os_model->isEditable($this->uri->segment(3));
        $this->data["qrCode"] = $this->os_model->getQrCode(
            $this->uri->segment(3),
            $this->data["configuration"]["pix_key"],
            $this->data["emitente"]
        );
        $this->data["modalGerarPagamento"] = $this->load->view(
            "cobrancas/modalGerarPagamento",
            [
                "id" => $this->uri->segment(3),
                "tipo" => "os",
            ],
            true
        );
        $this->data["view"] = "os/visualizarOs";
        $this->data["chaveFormatada"] = $this->formatarChave($this->data["configuration"]["pix_key"]);

        if ($return = $this->os_model->valorTotalOS($this->uri->segment(3))) {
            $this->data["totalServico"] = $return["totalServico"];
            $this->data["totalProdutos"] = $return["totalProdutos"];
        }

        return $this->layout();
    }

    public function validarCPF($cpf)
    {
        $cpf = preg_replace("/[^0-9]/", "", $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1+$/', $cpf)) {
            return false;
        }
        $soma1 = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma1 += $cpf[$i] * (10 - $i);
        }
        $resto1 = $soma1 % 11;
        $dv1 = $resto1 < 2 ? 0 : 11 - $resto1;
        if ($dv1 != $cpf[9]) {
            return false;
        }
        $soma2 = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma2 += $cpf[$i] * (11 - $i);
        }
        $resto2 = $soma2 % 11;
        $dv2 = $resto2 < 2 ? 0 : 11 - $resto2;

        return $dv2 == $cpf[10];
    }

    public function validarCNPJ($cnpj)
    {
        $cnpj = preg_replace("/[^0-9]/", "", $cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }
        $soma1 = 0;
        for ($i = 0, $pos = 5; $i < 12; $i++, $pos--) {
            $pos = $pos < 2 ? 9 : $pos;
            $soma1 += $cnpj[$i] * $pos;
        }
        $dv1 = $soma1 % 11 < 2 ? 0 : 11 - ($soma1 % 11);
        if ($dv1 != $cnpj[12]) {
            return false;
        }
        $soma2 = 0;
        for ($i = 0, $pos = 6; $i < 13; $i++, $pos--) {
            $pos = $pos < 2 ? 9 : $pos;
            $soma2 += $cnpj[$i] * $pos;
        }
        $dv2 = $soma2 % 11 < 2 ? 0 : 11 - ($soma2 % 11);

        return $dv2 == $cnpj[13];
    }

    public function formatarChave($chave)
    {
        if ($this->validarCPF($chave)) {
            return substr($chave, 0, 3) . "." . substr($chave, 3, 3) . "." . substr($chave, 6, 3) . "-" . substr($chave, 9);
        } elseif ($this->validarCNPJ($chave)) {
            return substr($chave, 0, 2) .
                "." .
                substr($chave, 2, 3) .
                "." .
                substr($chave, 5, 3) .
                "/" .
                substr($chave, 8, 4) .
                "-" .
                substr($chave, 12);
        } elseif (strlen($chave) === 11) {
            return "(" . substr($chave, 0, 2) . ") " . substr($chave, 2, 5) . "-" . substr($chave, 7);
        }

        return $chave;
    }

    public function imprimir()
    {
        if (!$this->uri->segment(3) || !is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata("error", "Item não pode ser encontrado, parâmetro não foi passado corretamente.");
            redirect("mapos");
        }

        if (!$this->permission->checkPermission($this->session->userdata("permissao"), "vOs")) {
            $this->session->set_flashdata("error", "Você não tem permissão para visualizar O.S.");
            redirect(base_url());
        }

        $this->data["custom_error"] = "";
        $this->load->model("mapos_model");
        $this->data["result"] = $this->os_model->getById($this->uri->segment(3));
        $this->data["produtos"] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data["servicos"] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data["anexos"] = $this->os_model->getAnexos($this->uri->segment(3));
        $this->data["emitente"] = $this->mapos_model->getEmitente();
        if ($this->data["configuration"]["pix_key"]) {
            $this->data["qrCode"] = $this->os_model->getQrCode(
                $this->uri->segment(3),
                $this->data["configuration"]["pix_key"],
                $this->data["emitente"]
            );
            $this->data["chaveFormatada"] = $this->formatarChave($this->data["configuration"]["pix_key"]);
        }

        $this->data["imprimirAnexo"] = isset($_ENV["IMPRIMIR_ANEXOS"])
            ? filter_var($_ENV["IMPRIMIR_ANEXOS"] ?? false, FILTER_VALIDATE_BOOLEAN)
            : false;

        $this->load->view("os/imprimirOs", $this->data);
    }

    public function imprimirTermica()
    {
        if (!$this->uri->segment(3) || !is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata("error", "Item não pode ser encontrado, parâmetro não foi passado corretamente.");
            redirect("mapos");
        }

        if (!$this->permission->checkPermission($this->session->userdata("permissao"), "vOs")) {
            $this->session->set_flashdata("error", "Você não tem permissão para visualizar O.S.");
            redirect(base_url());
        }

        $this->data["custom_error"] = "";
        $this->load->model("mapos_model");
        $this->data["result"] = $this->os_model->getById($this->uri->segment(3));
        $this->data["produtos"] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data["servicos"] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data["emitente"] = $this->mapos_model->getEmitente();
        $this->data["qrCode"] = $this->os_model->getQrCode(
            $this->uri->segment(3),
            $this->data["configuration"]["pix_key"],
            $this->data["emitente"]
        );
        $this->data["chaveFormatada"] = $this->formatarChave($this->data["configuration"]["pix_key"]);

        $this->load->view("os/imprimirOsTermica", $this->data);
    }

    public function enviar_email()
    {
        if (!$this->uri->segment(3) || !is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata("error", "Item não pode ser encontrado, parâmetro não foi passado corretamente.");
            redirect("mapos");
        }

        if (!$this->permission->checkPermission($this->session->userdata("permissao"), "vOs")) {
            $this->session->set_flashdata("error", "Você não tem permissão para enviar O.S. por e-mail.");
            redirect(base_url());
        }

        $this->load->model("mapos_model");
        $this->load->model("usuarios_model");
        $this->data["result"] = $this->os_model->getById($this->uri->segment(3));
        if (!isset($this->data["result"]->email)) {
            $this->session->set_flashdata("error", "O cliente não tem e-mail cadastrado.");
            redirect(site_url("os"));
        }

        $this->data["produtos"] = $this->os_model->getProdutos($this->uri->segment(3));
        $this->data["servicos"] = $this->os_model->getServicos($this->uri->segment(3));
        $this->data["emitente"] = $this->mapos_model->getEmitente();

        if (!isset($this->data["emitente"]->email)) {
            $this->session->set_flashdata("error", "Efetue o cadastro dos dados de emitente");
            redirect(site_url("os"));
        }

        $idOs = $this->uri->segment(3);

        $emitente = $this->data["emitente"];
        $tecnico = $this->usuarios_model->getById($this->data["result"]->usuarios_id);

        // Verificar configuração de notificação
        $ValidarEmail = false;
        if ($this->data["configuration"]["os_notification"] != "nenhum") {
            $remetentes = [];
            switch ($this->data["configuration"]["os_notification"]) {
                case "todos":
                    array_push($remetentes, $this->data["result"]->email);
                    array_push($remetentes, $tecnico->email);
                    array_push($remetentes, $emitente->email);
                    $ValidarEmail = true;
                    break;
                case "cliente":
                    array_push($remetentes, $this->data["result"]->email);
                    $ValidarEmail = true;
                    break;
                case "tecnico":
                    array_push($remetentes, $tecnico->email);
                    break;
                case "emitente":
                    array_push($remetentes, $emitente->email);
                    break;
                default:
                    array_push($remetentes, $this->data["result"]->email);
                    $ValidarEmail = true;
                    break;
            }

            if ($ValidarEmail) {
                if (empty($this->data["result"]->email) || !filter_var($this->data["result"]->email, FILTER_VALIDATE_EMAIL)) {
                    $this->session->set_flashdata("error", "Por favor preencha o email do cliente");
                    redirect(site_url("os/visualizar/") . $this->uri->segment(3));
                }
            }

            $enviouEmail = $this->enviarOsPorEmail($idOs, $remetentes, "Ordem de Serviço");

            if ($enviouEmail) {
                $this->session->set_flashdata("success", "O email está sendo processado e será enviado em breve.");
                log_info(
                    "Enviou e-mail para o cliente: " . $this->data["result"]->nomeCliente . ". E-mail: " . $this->data["result"]->email
                );
                redirect(site_url("os"));
            } else {
                $this->session->set_flashdata("error", "Ocorreu um erro ao enviar e-mail.");
                redirect(site_url("os"));
            }
        }

        $this->session->set_flashdata(
            "success",
            "O sistema está com uma configuração ativada para não notificar. Entre em contato com o administrador."
        );
        redirect(site_url("os"));
    }

    private function devolucaoEstoque($id)
    {
        if ($produtos = $this->os_model->getProdutos($id)) {
            $this->load->model("produtos_model");
            if ($this->data["configuration"]["control_estoque"]) {
                foreach ($produtos as $p) {
                    if (floatval($p->preco) > 0) {
                        $this->produtos_model->updateEstoque($p->produtos_id, $p->quantidade, "+");
                        log_info(
                            "ESTOQUE: Produto id " .
                                $p->produtos_id .
                                " voltou ao estoque. Quantidade: " .
                                $p->quantidade .
                                ". Motivo: Cancelamento/Exclusão"
                        );
                    }
                }
            }
        }
    }

    private function debitarEstoque($id)
    {
        if ($produtos = $this->os_model->getProdutos($id)) {
            $this->load->model("produtos_model");
            if ($this->data["configuration"]["control_estoque"]) {
                foreach ($produtos as $p) {
                    $this->produtos_model->updateEstoque($p->produtos_id, $p->quantidade, "-");
                    log_info(
                        "ESTOQUE: Produto id " .
                            $p->produtos_id .
                            " baixa do estoque. Quantidade: " .
                            $p->quantidade .
                            ". Motivo: Mudou status que já estava Cancelado para outro"
                    );
                }
            }
        }
    }

    public function excluir()
    {
        if (!$this->permission->checkPermission($this->session->userdata("permissao"), "dOs")) {
            $this->session->set_flashdata("error", "Você não tem permissão para excluir O.S.");
            redirect(base_url());
        }

        $id = $this->input->post("id");
        $os = $this->os_model->getByIdCobrancas($id);
        if ($os == null) {
            $os = $this->os_model->getById($id);
            if ($os == null) {
                $this->session->set_flashdata("error", "Erro ao tentar excluir OS.");
                redirect(base_url() . "index.php/os/gerenciar/");
            }
        }

        if (!$this->os_model->isEditable($id)) {
            $this->session->set_flashdata("error", "Esta OS já foi faturada ou cancelada e não pode ser excluída.");
            redirect(site_url("os/gerenciar/"));
        }

        if (isset($os->idCobranca) != null) {
            if ($os->status == "canceled") {
                $this->os_model->delete("cobrancas", "os_id", $id);
            } else {
                $this->session->set_flashdata(
                    "error",
                    "Existe uma cobrança associada a esta OS, deve cancelar e/ou excluir a cobrança primeiro!"
                );
                redirect(site_url("os/gerenciar/"));
            }
        }

        $osStockRefund = $this->os_model->getById($id);
        //Verifica para poder fazer a devolução do produto para o estoque caso OS seja excluida.
        if (strtolower($osStockRefund->status) != "cancelado") {
            $this->devolucaoEstoque($id);
        }

        $this->os_model->delete("servicos_os", "os_id", $id);
        $this->os_model->delete("produtos_os", "os_id", $id);
        $this->os_model->delete("anexos", "os_id", $id);
        $this->os_model->delete("os", "idOs", $id);
        if ((int) $os->faturado === 1) {
            $this->os_model->delete("lancamentos", "descricao", "Fatura de OS - #${id}");
        }

        log_info("Removeu uma OS. ID: " . $id);
        $this->session->set_flashdata("success", "OS excluída com sucesso!");
        redirect(site_url("os/gerenciar/"));
    }

    public function autoCompleteProduto()
    {
        if (isset($_GET["term"])) {
            $q = strtolower($_GET["term"]);
            $this->os_model->autoCompleteProduto($q);
        }
    }

    public function autoCompleteProdutoSaida()
    {
        if (isset($_GET["term"])) {
            $q = strtolower($_GET["term"]);
            $this->os_model->autoCompleteProdutoSaida($q);
        }
    }

    public function autoCompleteCliente()
    {
        if (isset($_GET["term"])) {
            $q = strtolower($_GET["term"]);
            $this->os_model->autoCompleteCliente($q);
        }
    }

    public function autoCompleteUsuario()
    {
        if (isset($_GET["term"])) {
            $q = strtolower($_GET["term"]);
            $this->os_model->autoCompleteUsuario($q);
        }
    }

    public function autoCompleteTermoGarantia()
    {
        if (isset($_GET["term"])) {
            $q = strtolower($_GET["term"]);
            $this->os_model->autoCompleteTermoGarantia($q);
        }
    }

    public function autoCompleteServico()
    {
        if (isset($_GET["term"])) {
            $q = strtolower($_GET["term"]);
            $this->os_model->autoCompleteServico($q);
        }
    }

    public function adicionarProduto()
    {
        $this->load->library("form_validation");

        if ($this->form_validation->run("adicionar_produto_os") === false) {
            $errors = validation_errors();

            return $this->output->set_content_type("application/json")->set_status_header(400)->set_output(json_encode($errors));
        }

        $preco = $this->input->post("preco");
        $quantidade = $this->input->post("quantidade");
        $subtotal = $preco * $quantidade;
        $produto = $this->input->post("idProduto");
        $data = [
            "quantidade" => $quantidade,
            "subTotal" => $subtotal,
            "produtos_id" => $produto,
            "preco" => $preco,
            "os_id" => $this->input->post("idOsProduto"),
        ];

        $id = $this->input->post("idOsProduto");
        if (!$this->os_model->isEditable($id)) {
            return $this->output
                ->set_content_type("application/json")
                ->set_status_header(400)
                ->set_output(json_encode(["result" => false, "messages" => "Esta OS já foi faturada/cancelada e não pode ser alterada."]));
        }
        $os = $this->os_model->getById($id);
        if ($os == null) {
            $this->session->set_flashdata("error", "Erro ao tentar inserir produto na OS.");
            redirect(base_url() . "index.php/os/gerenciar/");
        }

        if ($this->os_model->add("produtos_os", $data) == true) {
            $this->load->model("produtos_model");

            if ($this->data["configuration"]["control_estoque"]) {
                $this->produtos_model->updateEstoque($produto, $quantidade, "-");
            }

            $this->db->set("desconto", 0.0);
            $this->db->set("valor_desconto", 0.0);
            $this->db->set("tipo_desconto", null);
            $this->db->where("idOs", $id);
            $this->db->update("os");

            log_info("Adicionou produto a uma OS. ID (OS): " . $this->input->post("idOsProduto"));

            return $this->output
                ->set_content_type("application/json")
                ->set_status_header(200)
                ->set_output(json_encode(["result" => true]));
        } else {
            return $this->output
                ->set_content_type("application/json")
                ->set_status_header(500)
                ->set_output(json_encode(["result" => false]));
        }
    }

    public function excluirProduto()
    {
        $id = $this->input->post("idProduto");
        $idOs = $this->input->post("idOs");

        if (!$this->os_model->isEditable($idOs)) {
            return $this->output
                ->set_content_type("application/json")
                ->set_status_header(400)
                ->set_output(json_encode(["result" => false, "messages" => "Esta OS já foi faturada/cancelada e não pode ser alterada."]));
        }

        $os = $this->os_model->getById($idOs);
        if ($os == null) {
            $this->session->set_flashdata("error", "Erro ao tentar excluir produto na OS.");
            redirect(base_url() . "index.php/os/gerenciar/");
        }

        $produtoOs = $this->db->get_where("produtos_os", ["idProdutos_os" => $id])->row();
        $deveDevolverEstoque = $produtoOs && floatval($produtoOs->preco) > 0;

        if ($this->os_model->delete("produtos_os", "idProdutos_os", $id) == true) {
            $quantidade = $this->input->post("quantidade");
            $produto = $this->input->post("produto");

            $this->load->model("produtos_model");

            if ($this->data["configuration"]["control_estoque"] && $deveDevolverEstoque) {
                $this->produtos_model->updateEstoque($produto, $quantidade, "+");
            }

            $this->db->set("desconto", 0.0);
            $this->db->set("valor_desconto", 0.0);
            $this->db->set("tipo_desconto", null);
            $this->db->where("idOs", $idOs);
            $this->db->update("os");

            log_info("Removeu produto de uma OS. ID (OS): " . $idOs);

            echo json_encode(["result" => true]);
        } else {
            echo json_encode(["result" => false]);
        }
    }

    public function adicionarServico()
    {
        $this->load->library("form_validation");

        if ($this->form_validation->run("adicionar_servico_os") === false) {
            $errors = validation_errors();

            return $this->output->set_content_type("application/json")->set_status_header(400)->set_output(json_encode($errors));
        }

        $idOsServico = $this->input->post("idOsServico");
        if (!$this->os_model->isEditable($idOsServico)) {
            return $this->output
                ->set_content_type("application/json")
                ->set_status_header(400)
                ->set_output(json_encode(["result" => false, "messages" => "Esta OS já foi faturada/cancelada e não pode ser alterada."]));
        }

        $data = [
            "servicos_id" => $this->input->post("idServico"),
            "quantidade" => $this->input->post("quantidade"),
            "preco" => $this->input->post("preco"),
            "os_id" => $idOsServico,
            "subTotal" => $this->input->post("preco") * $this->input->post("quantidade"),
        ];

        if ($this->os_model->add("servicos_os", $data) == true) {
            log_info("Adicionou serviço a uma OS. ID (OS): " . $this->input->post("idOsServico"));

            $this->db->set("desconto", 0.0);
            $this->db->set("valor_desconto", 0.0);
            $this->db->set("tipo_desconto", null);
            $this->db->where("idOs", $this->input->post("idOsServico"));
            $this->db->update("os");

            return $this->output
                ->set_content_type("application/json")
                ->set_status_header(200)
                ->set_output(json_encode(["result" => true]));
        } else {
            return $this->output
                ->set_content_type("application/json")
                ->set_status_header(500)
                ->set_output(json_encode(["result" => false]));
        }
    }

    public function excluirServico()
    {
        $ID = $this->input->post("idServico");
        $idOs = $this->input->post("idOs");

        if (!$this->os_model->isEditable($idOs)) {
            return $this->output
                ->set_content_type("application/json")
                ->set_status_header(400)
                ->set_output(json_encode(["result" => false, "messages" => "Esta OS já foi faturada/cancelada e não pode ser alterada."]));
        }

        if ($this->os_model->delete("servicos_os", "idServicos_os", $ID) == true) {
            log_info("Removeu serviço de uma OS. ID (OS): " . $idOs);
            $this->db->set("desconto", 0.0);
            $this->db->set("valor_desconto", 0.0);
            $this->db->set("tipo_desconto", null);
            $this->db->where("idOs", $idOs);
            $this->db->update("os");
            echo json_encode(["result" => true]);
        } else {
            echo json_encode(["result" => false]);
        }
    }

    public function anexar()
    {
        $this->load->library("upload");
        $this->load->library("image_lib");

        $directory =
            FCPATH .
            "assets" .
            DIRECTORY_SEPARATOR .
            "anexos" .
            DIRECTORY_SEPARATOR .
            date("m-Y") .
            DIRECTORY_SEPARATOR .
            "OS-" .
            $this->input->post("idOsServico");

        // If it exist, check if it's a directory
        if (!is_dir($directory . DIRECTORY_SEPARATOR . "thumbs")) {
            // make directory for images and thumbs
            try {
                mkdir($directory . DIRECTORY_SEPARATOR . "thumbs", 0755, true);
            } catch (Exception $e) {
                echo json_encode(["result" => false, "mensagem" => $e->getMessage()]);
                exit();
            }
        }

        $upload_conf = [
            "upload_path" => $directory,
            "allowed_types" => "jpg|png|gif|jpeg|JPG|PNG|GIF|JPEG|pdf|PDF|cdr|CDR|docx|DOCX|txt", // formatos permitidos para anexos de os
            "max_size" => 0,
        ];

        $this->upload->initialize($upload_conf);

        foreach ($_FILES["userfile"] as $key => $val) {
            $i = 1;
            foreach ($val as $v) {
                $field_name = "file_" . $i;
                $_FILES[$field_name][$key] = $v;
                $i++;
            }
        }
        unset($_FILES["userfile"]);

        $error = [];
        $success = [];

        foreach ($_FILES as $field_name => $file) {
            if (!$this->upload->do_upload($field_name)) {
                $error["upload"][] = $this->upload->display_errors();
            } else {
                $upload_data = $this->upload->data();

                // Gera um nome de arquivo aleatório mantendo a extensão original
                $new_file_name = uniqid() . "." . pathinfo($upload_data["file_name"], PATHINFO_EXTENSION);
                $new_file_path = $upload_data["file_path"] . $new_file_name;

                rename($upload_data["full_path"], $new_file_path);

                if ($upload_data["is_image"] == 1) {
                    $resize_conf = [
                        "source_image" => $new_file_path,
                        "new_image" => $upload_data["file_path"] . "thumbs" . DIRECTORY_SEPARATOR . "thumb_" . $new_file_name,
                        "width" => 200,
                        "height" => 125,
                    ];

                    $this->image_lib->initialize($resize_conf);

                    if (!$this->image_lib->resize()) {
                        $error["resize"][] = $this->image_lib->display_errors();
                    } else {
                        $success[] = $upload_data;
                        $this->load->model("Os_model");
                        $result = $this->Os_model->anexar(
                            $this->input->post("idOsServico"),
                            $new_file_name,
                            base_url(
                                "assets" .
                                    DIRECTORY_SEPARATOR .
                                    "anexos" .
                                    DIRECTORY_SEPARATOR .
                                    date("m-Y") .
                                    DIRECTORY_SEPARATOR .
                                    "OS-" .
                                    $this->input->post("idOsServico")
                            ),
                            "thumb_" . $new_file_name,
                            $directory
                        );
                        if (!$result) {
                            $error["db"][] = "Erro ao inserir no banco de dados.";
                        }
                    }
                } else {
                    $success[] = $upload_data;

                    $this->load->model("Os_model");

                    $result = $this->Os_model->anexar(
                        $this->input->post("idOsServico"),
                        $new_file_name,
                        base_url(
                            "assets" .
                                DIRECTORY_SEPARATOR .
                                "anexos" .
                                DIRECTORY_SEPARATOR .
                                date("m-Y") .
                                DIRECTORY_SEPARATOR .
                                "OS-" .
                                $this->input->post("idOsServico")
                        ),
                        "",
                        $directory
                    );
                    if (!$result) {
                        $error["db"][] = "Erro ao inserir no banco de dados.";
                    }
                }
            }
        }

        if (count($error) > 0) {
            echo json_encode(["result" => false, "mensagem" => "Ocorreu um erro ao processar os arquivos.", "errors" => $error]);
        } else {
            log_info("Adicionou anexo(s) a uma OS. ID (OS): " . $this->input->post("idOsServico"));
            echo json_encode(["result" => true, "mensagem" => "Arquivo(s) anexado(s) com sucesso."]);
        }
    }

    public function excluirAnexo($id = null)
    {
        if ($id == null || !is_numeric($id)) {
            echo json_encode(["result" => false, "mensagem" => "Erro ao tentar excluir anexo."]);
        } else {
            $this->db->where("idAnexos", $id);
            $file = $this->db->get("anexos", 1)->row();
            $idOs = $this->input->post("idOs");

            unlink($file->path . DIRECTORY_SEPARATOR . $file->anexo);

            if ($file->thumb != null) {
                unlink($file->path . DIRECTORY_SEPARATOR . "thumbs" . DIRECTORY_SEPARATOR . $file->thumb);
            }

            if ($this->os_model->delete("anexos", "idAnexos", $id) == true) {
                log_info("Removeu anexo de uma OS. ID (OS): " . $idOs);
                echo json_encode(["result" => true, "mensagem" => "Anexo excluído com sucesso."]);
            } else {
                echo json_encode(["result" => false, "mensagem" => "Erro ao tentar excluir anexo."]);
            }
        }
    }

    public function downloadanexo($id = null)
    {
        if ($id != null && is_numeric($id)) {
            $this->db->where("idAnexos", $id);
            $file = $this->db->get("anexos", 1)->row();

            $this->load->library("zip");
            $path = $file->path;
            $this->zip->read_file($path . "/" . $file->anexo);
            $this->zip->download("file" . date("d-m-Y-H.i.s") . ".zip");
        }
    }

    public function adicionarDesconto()
    {
        if ($this->input->post("desconto") == "") {
            return $this->output
                ->set_content_type("application/json")
                ->set_status_header(400)
                ->set_output(json_encode(["messages" => "Campo desconto vazio"]));
        } else {
            $idOs = $this->input->post("idOs");
            $data = [
                "tipo_desconto" => $this->input->post("tipoDesconto"),
                "desconto" => $this->input->post("desconto"),
                "valor_desconto" => $this->input->post("resultado"),
            ];
            $editavel = $this->os_model->isEditable($idOs);
            if (!$editavel) {
                return $this->output
                    ->set_content_type("application/json")
                    ->set_status_header(400)
                    ->set_output(
                        json_encode(["result" => false, "messages", "Desconto não pode ser adiciona. Os não ja Faturada/Cancelada"])
                    );
            }
            if ($this->os_model->edit("os", $data, "idOs", $idOs) == true) {
                log_info("Adicionou um desconto na OS. ID: " . $idOs);

                return $this->output
                    ->set_content_type("application/json")
                    ->set_status_header(201)
                    ->set_output(json_encode(["result" => true, "messages" => "Desconto adicionado com sucesso!"]));
            } else {
                log_info("Ocorreu um erro ao tentar adiciona desconto a OS: " . $idOs);

                return $this->output
                    ->set_content_type("application/json")
                    ->set_status_header(400)
                    ->set_output(json_encode(["result" => false, "messages", "Ocorreu um erro ao tentar adiciona desconto a OS."]));
            }
        }

        return $this->output
            ->set_content_type("application/json")
            ->set_status_header(400)
            ->set_output(json_encode(["result" => false, "messages", "Ocorreu um erro ao tentar adiciona desconto a OS."]));
    }

    public function faturar()
    {
        $this->load->library("form_validation");
        $this->data["custom_error"] = "";

        if ($this->form_validation->run("receita") == false) {
            $this->data["custom_error"] = validation_errors() ? '<div class="form_error">' . validation_errors() . "</div>" : false;
        } else {
            $vencimento = $this->input->post("vencimento");
            $recebimento = $this->input->post("recebimento");

            try {
                $vencimento = DateTime::createFromFormat("d/m/Y", $vencimento)->format("Y-m-d");
                if ($recebimento != null) {
                    $recebimento = DateTime::createFromFormat("d/m/Y", $recebimento)->format("Y-m-d");
                }
            } catch (Exception $e) {
                $vencimento = date("Y-m-d");
            }

            $os_id = $this->input->post("os_id");
            $valorTotalData = $this->os_model->valorTotalOS($os_id);

            $valorTotalServico = $valorTotalData["totalServico"];
            $valorTotalProduto = $valorTotalData["totalProdutos"];
            $valorDesconto = $valorTotalData["valor_desconto"];

            $valorTotal = $valorTotalServico + $valorTotalProduto;
            $valorTotalComDesconto = $valorTotal - $valorDesconto;

            $data = [
                "descricao" => set_value("descricao"),
                "valor" => $valorTotal,
                "tipo_desconto" => "real",
                "desconto" => $valorDesconto > 0 ? $valorTotalComDesconto : 0,
                "valor_desconto" => $valorDesconto > 0 ? $valorDesconto : $valorTotal,
                "clientes_id" => $this->input->post("clientes_id"),
                "data_vencimento" => $vencimento,
                "data_pagamento" => $recebimento,
                "baixado" => $this->input->post("recebido") ?: 0,
                "cliente_fornecedor" => set_value("cliente"),
                "forma_pgto" => $this->input->post("formaPgto"),
                "tipo" => $this->input->post("tipo"),
                "observacoes" => set_value("observacoes"),
                "usuarios_id" => $this->session->userdata("id_admin"),
            ];

            $this->db->trans_start();

            $editavel = $this->os_model->isEditable($os_id);
            if (!$editavel) {
                $this->db->trans_rollback();
                return $this->output
                    ->set_content_type("application/json")
                    ->set_status_header(400)
                    ->set_output(json_encode(["result" => false]));
            }

            if ($this->os_model->add("lancamentos", $data)) {
                $this->db->set("faturado", 1);
                $this->db->set("valorTotal", $valorTotal);

                if ($valorDesconto > 0) {
                    $this->db->set("desconto", $valorTotalComDesconto);
                    $this->db->set("valor_desconto", $valorDesconto);
                } else {
                    $this->db->set("desconto", 0);
                    $this->db->set("valor_desconto", $valorTotal);
                }

                $this->db->set("status", "Faturado");
                $this->db->where("idOs", $os_id);
                $this->db->update("os");

                log_info("Faturou uma OS. ID: " . $os_id);

                $this->db->trans_complete();

                if ($this->db->trans_status() === false) {
                    $this->session->set_flashdata("error", "Ocorreu um erro ao tentar faturar OS.");
                    $json = ["result" => false];
                } else {
                    $this->session->set_flashdata("success", "OS faturada com sucesso!");
                    $json = ["result" => true];
                }
            } else {
                $this->db->trans_rollback();
                $this->session->set_flashdata("error", "Ocorreu um erro ao tentar faturar OS.");
                $json = ["result" => false];
            }

            echo json_encode($json);
            exit();
        }

        $this->session->set_flashdata("error", "Ocorreu um erro ao tentar faturar OS.");
        $json = ["result" => false];
        echo json_encode($json);
    }

    private function enviarOsPorEmail($idOs, $remetentes, $assunto)
    {
        $dados = [];

        $this->load->model("mapos_model");
        $dados["result"] = $this->os_model->getById($idOs);
        if (!isset($dados["result"]->email)) {
            return false;
        }

        $dados["produtos"] = $this->os_model->getProdutos($idOs);
        $dados["servicos"] = $this->os_model->getServicos($idOs);
        $dados["emitente"] = $this->mapos_model->getEmitente();
        $emitente = $dados["emitente"];
        if (!isset($emitente->email)) {
            return false;
        }

        $html = $this->load->view("os/emails/os", $dados, true);

        $this->load->model("email_model");

        $remetentes = array_unique($remetentes);
        foreach ($remetentes as $remetente) {
            if ($remetente) {
                $headers = ["From" => $emitente->email, "Subject" => $assunto, "Return-Path" => ""];
                $email = [
                    "to" => $remetente,
                    "message" => $html,
                    "status" => "pending",
                    "date" => date("Y-m-d H:i:s"),
                    "headers" => serialize($headers),
                ];
                $this->email_model->add("email_queue", $email);
            } else {
                log_info("Email não adicionado a Lista de envio de e-mails. Verifique se o remetente esta cadastrado. OS ID: " . $idOs);
            }
        }

        return true;
    }

    public function adicionarAnotacao()
    {
        $this->load->library("form_validation");
        if ($this->form_validation->run("anotacoes_os") == false) {
            echo json_encode(validation_errors());
        } else {
            $data = [
                "anotacao" => "[" . $this->session->userdata("nome_admin") . "] " . $this->input->post("anotacao"),
                "data_hora" => date("Y-m-d H:i:s"),
                "os_id" => $this->input->post("os_id"),
            ];

            if ($this->os_model->add("anotacoes_os", $data) == true) {
                log_info("Adicionou anotação a uma OS. ID (OS): " . $this->input->post("os_id"));
                echo json_encode(["result" => true]);
            } else {
                echo json_encode(["result" => false]);
            }
        }
    }

    public function excluirAnotacao()
    {
        $id = $this->input->post("idAnotacao");
        $idOs = $this->input->post("idOs");

        if ($this->os_model->delete("anotacoes_os", "idAnotacoes", $id) == true) {
            log_info("Removeu anotação de uma OS. ID (OS): " . $idOs);
            echo json_encode(["result" => true]);
        } else {
            echo json_encode(["result" => false]);
        }
    }

    public function alterarStatus()
    {
        // Apenas para requisições AJAX
        if (!$this->input->is_ajax_request()) {
            exit("No direct script access allowed");
        }

        // Verifica a permissão de edição
        if (!$this->permission->checkPermission($this->session->userdata("permissao"), "eOs")) {
            $this->output
                ->set_content_type("application/json")
                ->set_status_header(403)
                ->set_output(json_encode(["success" => false, "message" => "Você não tem permissão para editar O.S."]));
            return;
        }

        $idOs = $this->input->post("idOs");
        $novoStatus = $this->input->post("novoStatus");

        // Validação básica
        if (!$idOs || !$novoStatus) {
            $this->output
                ->set_content_type("application/json")
                ->set_status_header(400)
                ->set_output(json_encode(["success" => false, "message" => "Dados inválidos."]));
            return;
        }

        // Verifica se a OS pode ser editada
        if (!$this->os_model->isEditable($idOs)) {
            //
            $this->output
                ->set_content_type("application/json")
                ->set_status_header(400)
                ->set_output(json_encode(["success" => false, "message" => "Esta OS não pode ter seu status alterado."]));
            return;
        }

        // Pega o status antigo para a lógica de devolução de estoque
        $osAntiga = $this->os_model->getById($idOs); //
        $statusAntigo = $osAntiga->status;

        // Lógica para devolução de estoque ao cancelar
        if (strtolower($novoStatus) == "cancelado" && strtolower($statusAntigo) != "cancelado") {
            $this->devolucaoEstoque($idOs); //
        }

        // Lógica para debitar estoque ao sair do status "Cancelado"
        if (strtolower($statusAntigo) == "cancelado" && strtolower($novoStatus) != "cancelado") {
            $this->debitarEstoque($idOs); //
        }

        $data = ["status" => $novoStatus];

        if ($this->os_model->edit("os", $data, "idOs", $idOs)) {
            log_info("Status da OS alterado. ID: " . $idOs . " Novo Status: " . $novoStatus);

            // Prepara a resposta JSON com o novo token CSRF para a próxima requisição
            $response = [
                "success" => true,
                "message" => "Status atualizado com sucesso!",
                "csrf_hash" => $this->security->get_csrf_hash(), // Adicione esta linha
            ];

            $this->output->set_content_type("application/json")->set_status_header(200)->set_output(json_encode($response)); // Envia a resposta completa
        } else {
            $this->output
                ->set_content_type("application/json")
                ->set_status_header(500)
                ->set_output(json_encode(["success" => false, "message" => "Ocorreu um erro ao atualizar o status."]));
        }
    }
}
