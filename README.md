<div align="center">

  <img src="https://github.com/MilsonElias/mapos/tree/master/docs/dashboard.jpeg" alt="Map-OS Dashboard" width="100%" style="border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);" />

### Sistema Completo para Gestão de Ordens de Serviço e Vendas

  <p align="center">
    <a href="https://github.com/RamonSilva20/mapos/releases"><img src="https://img.shields.io/badge/Versão-4.54.0-0284c7.svg?style=for-the-badge&logo=tag&logoColor=white" alt="Versão"></a>
    <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-%3E%3D_8.4-777bb4.svg?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.4"></a>
    <a href="https://www.mysql.com/"><img src="https://img.shields.io/badge/MySQL-%3E%3D_5.7_%7C_8.0-4479a1.svg?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"></a>
    <a href="LICENSE.txt"><img src="https://img.shields.io/badge/Licença-Apache_2.0-16a34a.svg?style=for-the-badge&logo=apache&logoColor=white" alt="Licença"></a>
  </p>

  <p align="center">
    <a href="#-sobre-o-projeto">Sobre</a> •
    <a href="#-recursos-principais">Recursos</a> •
    <a href="#-instalação">Instalação</a> •
    <a href="#-atualização">Atualização</a> •
    <a href="#-gateways-de-pagamento">Pagamentos</a> •
    <a href="#-hospedagem-parceira">Hospedagem</a> •
    <a href="#-apoie-o-projeto">Doações</a> •
    <a href="#-comunidade--contato">Comunidade</a>
  </p>

</div>

---

## Sobre o Projeto

O **Map-OS** é uma solução robusta, gratuita e de código aberto voltada para pequenas e médias empresas, assistências técnicas e prestadores de serviços. O sistema centraliza todo o fluxo de trabalho operacional: desde o atendimento inicial e emissão de Ordens de Serviço até o controle financeiro, estoque, vendas de balcão e cobranças automatizadas via PIX, boleto e cartão.

Mantido e desenvolvido ativamente pela **[MountBit](https://mountbit.com.br)** em conjunto com a comunidade open source.

---

## Recursos Principais

| Módulo                          | Funcionalidades em Destaque                                                                                                                                                       |
| :------------------------------ | :-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Ordens de Serviço**        | Status configuráveis, termos de garantia personalizados, laudos técnicos, fotos/anexos, impressão térmica e A4 e envio direto via WhatsApp/E-mail. |
| **Clientes & Fornecedores**  | Cadastro completo com histórico detalhado de compras e serviços, busca rápida por CNPJ e integração com CEP.                                                                  |
| **Portal do Cliente (Mine)** | Área externa exclusiva onde o cliente pode acompanhar o andamento da sua OS em tempo real, aprovar/recusar orçamentos e emitir faturas.                                           |
| **Controle de Estoque**      | Cadastro de produtos com código de barras, estoque mínimo com alerta, histórico de movimentação e cálculo automático de margem de lucro.                                          |
| **Vendas & PDV**             | Frente de caixa ágil para vendas de balcão, aplicação de descontos, faturamento direto e emissão de comprovantes.                                                                 |
| **Financeiro & Cobranças**   | Contas a pagar/receber, fluxo de caixa, conciliação e geração automática de cobranças com QR Code PIX dinâmico e boletos.                                                         |
| **Relatórios & Auditoria**   | Relatórios com exportação para PDF e Excel (`.xlsx`), além de trilha de auditoria completa das ações dos usuários.                                                                |
| **Segurança & Permissões**   | Controle de acesso granular por perfil de usuário, logs de autenticação e proteção contra CSRF/XSS.                                                                               |

---

## Gateways de Pagamento

O Map-OS possui integrações nativas prontas para uso com os principais intermediadores:

- **[Asaas](https://asaas.com/)**: Cobranças via PIX Dinâmico, Boletos e Cartão de Crédito com retorno automático.
- **[Mercado Pago](https://www.mercadopago.com.br/)**: Checkout transparente, PIX e Cartão.
- **[Efí (antiga Gerencianet)](https://sejaefi.com.br/)**: Emissão de boletos registrados e PIX com confirmação em tempo real via Webhook.

---

## Requisitos do Sistema

- **PHP**: `^8.4` (extensões recomendadas: `curl`, `gd`, `mbstring`, `openssl`, `zip`, `xml`)
- **Banco de Dados**: MySQL `>= 5.7` ou `>= 8.0` / MariaDB `>= 10.3`
- **Gerenciador de Dependências**: Composer `>= 2.x`
- **Servidor Web**: Apache (com `mod_rewrite` ativado), Nginx ou Docker

---

## Instalação

Escolha o método mais adequado para o seu ambiente:

### ⚡ Opção 1: Instalação Automatizada (Recomendado)

> [!TIP]
> Assista ao [Tutorial em Vídeo da Instalação Automatizada](https://www.youtube.com/watch?v=aZE-LW_YOE4) para acompanhar o passo a passo.

#### 🪟 Windows 10/11

1. Abra o **PowerShell** como **Administrador**;
2. Execute o comando abaixo:
    ```powershell
    PowerShell -command "& { iwr https://raw.githubusercontent.com/RamonSilva20/mapos/master/install.bat -OutFile MapOS_Install.bat }; .\MapOS_Install.bat"
    ```
3. Siga as instruções exibidas na tela.

#### 🐧 Linux (Ubuntu / Debian)

1. Abra o terminal do servidor ou acesse via SSH;
2. Obtenha privilégios de superusuário:
    ```bash
    sudo su
    ```
3. Execute o script de auto-instalação:
    ```bash
    curl -o MapOS_Install.sh -L https://raw.githubusercontent.com/RamonSilva20/mapos/master/install.sh && chmod +x MapOS_Install.sh && ./MapOS_Install.sh
    ```
4. Siga as orientações na tela.

---

### 🐳 Opção 2: Instalação via Docker

1. Certifique-se de possuir o [Docker](https://docs.docker.com/install/) e o [Docker Compose](https://docs.docker.com/compose/install/) instalados;
2. Clone este repositório ou faça o download dos arquivos;
3. Acesse o diretório `docker/` no terminal e execute:
    ```bash
    docker-compose up -d --force-recreate
    ```
4. Acesse no navegador: `http://localhost:8000/`;
5. No assistente de instalação, utilize os seguintes dados de banco:
    - **Host:** `mysql`
    - **Usuário:** `mapos`
    - **Senha:** `mapos`
    - **Banco de Dados:** `mapos`
    - **URL do Sistema:** `http://localhost:8000/`
6. O **PhpMyAdmin** estará disponível em: `http://localhost:8080/`.

> [!WARNING]
> A pasta `docker/data` armazena os dados persistentes do MySQL. Não a exclua para evitar perda de dados.

---

### 📦 Opção 3: Instalação Manual (XAMPP, Laragon, Apache, Nginx)

1. Faça o clone ou download do repositório para o diretório raiz do seu servidor web (ex: `htdocs` ou `www`);
2. Na raiz do projeto, instale as dependências via Composer:
    ```bash
    composer install --no-dev
    ```
3. Crie um banco de dados MySQL vazio (com collation `utf8mb4_unicode_ci`);
4. Acesse a URL do sistema no navegador e conclua o assistente de instalação;
5. Acesse **Configurações > Sistema > E-mail** e configure seu servidor SMTP;
6. Configure as **Tarefas Agendadas (Cron Jobs)** no servidor:
    ```cron
    # Envio de e-mails pendentes (a cada 2 minutos)
    */2 * * * * php /caminho/para/seu/projeto/index.php email/process

    # Tentativa de reenvio de falhas (a cada 5 minutos)
    */5 * * * * php /caminho/para/seu/projeto/index.php email/retry
    ```

---

## Procedimento de Atualização

### Atualização Manual

1. **Faça backup completo** do banco de dados e dos arquivos em **Configurações > Backup**;
2. Copie e preserve as seguintes pastas e arquivos da versão atual:
    - `assets/anexos/`
    - `assets/arquivos/`
    - `assets/uploads/`
    - `assets/userimage/`
    - Personalizações na pasta `assets/img/`
    - Arquivo de ambiente `application/.env`
3. Substitua o restante dos arquivos pelos da nova versão;
4. Execute na raiz do projeto:
    ```bash
    composer install --no-dev
    ```
5. Restaure as pastas de arquivos e o `.env` preservados no passo 2;
6. Execute a migração do banco de dados:
    - **Via Painel:** Acesse como Administrador em **Configurações > Sistema > Atualizar Banco de Dados**;
    - **Via CLI:** Execute o comando:
        ```bash
        php index.php tools migrate
        ```

### Atualização no Docker

1. Pare os contêineres: `docker-compose down`;
2. Realize o backup dos dados conforme instruções acima;
3. Atualize o código-fonte mantendo seu `.env` e pasta `docker/data`;
4. Suba novamente os contêineres:
    ```bash
    docker-compose up -d --force-recreate
    ```
5. Execute as migrações pelo painel ou terminal: `php index.php tools migrate`.

---

## Comandos de Linha de Comando (CLI Tools)

O Map-OS disponibiliza utilitários via terminal para manutenções e rotinas de sistema. Para listar todos os comandos disponíveis:

```bash
php index.php tools
```

- **Executar migrações do banco:** `php index.php tools migrate`
- **Processar fila de e-mails:** `php index.php email/process`
- **Reprocessar e-mails com erro:** `php index.php email/retry`

---

## Hospedagem Parceira

Em parceria oficial com o Projeto Map-OS, a **SysGO** oferece hospedagem otimizada e suporte especializado com excelente custo-benefício e estabilidade.

<p align="center">
  <a href="https://sysgo.com.br/mapos">
    <img src="https://sysgo.com.br/img-externo/mapos-github.jpg" alt="SysGO - MAP-OS Cloud Hosting" width="60%" style="border-radius: 8px;" />
  </a>
  <br>
  👉 <a href="https://sysgo.com.br/mapos"><strong>Clique aqui para conhecer os planos de Hospedagem Map-OS</strong></a>
</p>

---

## Apoie o Projeto

O **Map-OS** é um software livre sustentado pelo esforço de desenvolvedores voluntários. Se o sistema é útil no seu negócio e te ajuda a lucrar, considere apoiar financeiramente para garantir a continuidade, novas funcionalidades e correções de segurança.

<p align="center">
  <a href="https://donate.mapos.com.br">
    <img src="https://img.shields.io/badge/Apoiar_com_Doação-Fazer_Contribuição-ff5c5c.svg?style=for-the-badge&logo=heart&logoColor=white" alt="Doar para o Map-OS">
  </a>
</p>

---

## Comunidade & Contato

- 📧 **E-mail de Contato:** [contato@mapos.com.br](mailto:contato@mapos.com.br)
- 💬 **Comunidade no WhatsApp:** [Entrar no Grupo Oficial](https://chat.whatsapp.com/GVSg8tPQzXy0grfYpRfQps)
- 💡 **Sugestões e Discussões:** [GitHub Discussions](https://github.com/RamonSilva20/mapos/discussions)
- 🏢 **Desenvolvido e Mantido por:** [MountBit Soluções Web](https://mountbit.com.br)

---

## Histórico de Estrelas

<p align="center">
  <a href="https://star-history.com/#RamonSilva20/mapos&Date">
   <picture>
     <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=RamonSilva20/mapos&type=Date&theme=dark" />
     <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=RamonSilva20/mapos&type=Date" />
     <img alt="Star History Chart" src="https://api.star-history.com/svg?repos=RamonSilva20/mapos&type=Date" />
   </picture>
  </a>
</p>

---

## Autor & Contribuidores

<p align="center">
  <a href="https://github.com/RamonSilva20">
    <img src="https://images.weserv.nl/?url=github.com/RamonSilva20.png&mask=circle&w=120&h=120" alt="Ramon Silva" width="100px" /><br />
    <sub><b>Ramon Silva</b></sub>
  </a>
</p>

Agradecemos imensamente a todos os membros da comunidade que colaboram com o projeto:

<p align="center">
  <a href="https://github.com/RamonSilva20/mapos/graphs/contributors">
    <img src="https://contrib.rocks/image?repo=RamonSilva20/mapos" alt="Contribuidores do Map-OS" />
  </a>
</p>

---

## Licença

Este projeto é distribuído sob a licença **Apache License 2.0**. Consulte o arquivo [LICENSE.txt](LICENSE.txt) para obter mais informações.
