## Reservas Acadêmicas

O **Reservas Acadêmicas** é um sistema para gerenciamento e reserva de ambientes e recursos acadêmicos, como salas,
laboratórios, auditórios, bancadas, computadores e equipamentos. A plataforma permite consultar a disponibilidade dos
recursos, solicitar e cancelar reservas, acompanhar aprovações e gerenciar ambientes de acordo com o perfil do usuário
(discente, docente ou técnico).

Documentação completa na [wiki do projeto](https://github.com/edu-almeidaf/reservas-academicas/wiki).

### Dependências

- Docker
- Docker Compose

As portas `80`, `8080` e `4444` precisam estar livres.

### Como rodar

#### Clonar o repositório

```
$ git clone https://github.com/edu-almeidaf/reservas-academicas.git
$ cd reservas-academicas
```

#### Definir as variáveis de ambiente

```
$ cp .env.example .env
```

#### Instalar as dependências

```
$ ./run composer install
$ ./run codecept build
```

#### Subir os containers

```
$ ./run up -d
```

#### Criar o banco de dados e as tabelas

```
$ ./run db:reset
```

Logo após o `up`, o MySQL pode levar alguns segundos para aceitar conexões. Se o comando falhar, aguarde e rode de novo.

#### Popular o banco de dados

```
$ ./run db:populate
```

Acesse [localhost](http://localhost).

### Dados de acesso

O populate cria um usuário de cada perfil:

| Perfil | E-mail | Senha | Área |
|--------|--------|-------|------|
| Discente | `discente@utfpr.edu.br` | `12345678` | Área do Discente (`/student`) |
| Docente | `docente@utfpr.edu.br` | `12345678` | Área do Docente (`/teacher`) |
| Técnico (admin) | `tecnico@utfpr.edu.br` | `12345678` | Área Administrativa (`/admin`) |

Novos discentes e docentes podem se cadastrar em [localhost/signup](http://localhost/signup). Usuários técnicos
(administradores) são criados somente pelo populate.

### Reservas

Depois de entrar com qualquer um dos usuários, clique em **Minhas Reservas** (barra superior) ou acesse
[localhost/reservations](http://localhost/reservations). Cada usuário vê e gerencia somente as próprias reservas:

- **Solicitar** uma reserva em **Nova Reserva**: recurso, categoria, motivo, data, início e fim. Toda reserva nasce
  `pendente`.
- **Listar** as reservas, 10 por página, e **visualizar** os detalhes de uma delas.
- **Editar** e **remover** uma reserva enquanto ela estiver `pendente`.

A data não pode estar no passado, o fim deve ser depois do início, o recurso deve estar ativo e o horário não pode
conflitar com outra reserva `pendente` ou `deferido` do mesmo recurso.

Além dos usuários, o populate cria:

- as categorias de recurso e 12 recursos (2 inativos, que não aparecem no formulário), 3 deles com subdivisões;
- as categorias de reserva;
- 33 reservas futuras: 25 do discente (3 páginas na listagem, 2 delas já deferidas), 5 do docente e 3 do técnico.

### Galeria de imagens dos recursos

Só o técnico gerencia os recursos. Entre com `tecnico@utfpr.edu.br` e clique em **Recursos** (barra superior ou área
administrativa), ou acesse [localhost/resources](http://localhost/resources). Na página de cada recurso é possível:

- **Enviar** imagens JPEG ou PNG de até 2 MB, uma por vez. O tipo é conferido pelo conteúdo do arquivo, e o nome
  gravado é gerado pelo sistema.
- **Ver** as imagens já enviadas e **remover** uma delas (o registro e o arquivo).
- **Remover o recurso** com todas as suas imagens, se ele nunca foi usado em reservas. Os recursos inativos
  (`Laboratório de Eletrônica` e `Projetor Multimídia 2`) não têm reservas no populate.

As galerias começam vazias. Os arquivos ficam em `public/assets/uploads/resources/{id do recurso}/`. A permissão de
escrita nessa pasta é ajustada automaticamente quando os containers sobem.

### Subdivisões das reservas

Um recurso pode ter **subdivisões** (bancadas de um laboratório, palco e plateia de um auditório), e uma reserva pode
usar só algumas delas em vez do recurso inteiro (relação N×N entre `reservations` e `subdivisions`, pela tabela
`reservation_subdivision`).

- **Escolher as subdivisões:** em **Nova Reserva** (ou ao editar uma reserva pendente), escolha o recurso. As
  subdivisões ativas dele são carregadas por Ajax, sem recarregar a página, e aparecem como caixas de seleção. Sem
  nenhuma marcada, a reserva é do **recurso inteiro**.
- **Ver** as subdivisões na página da reserva e na coluna **Subdivisões** de **Minhas Reservas**, e **remover** uma
  delas da reserva pendente (botão **Remover** na página da reserva, ou desmarcando na edição).
- **Conflito de horário:** duas reservas do mesmo recurso no mesmo horário só conflitam se uma delas for do recurso
  inteiro ou se as duas tiverem alguma subdivisão em comum.
- **Gerenciar (técnico):** na página do recurso, em **Recursos**, o técnico cadastra e remove subdivisões. Uma
  subdivisão já usada em reservas não pode ser removida.

O populate cria 9 subdivisões no `Laboratório de Informática 1` (Bancada 1 a 4, a 4 inativa), no `Laboratório de
Redes` (Bancada A, Bancada B e Rack de servidores) e no `Auditório Central` (Palco e Plateia). A `Bancada 1` e a
`Bancada A` já têm reservas.

Para ver a requisição Ajax, abra o DevTools do navegador (F12) na aba **Network**, filtre por **Fetch/XHR** e troque o
recurso no formulário: a requisição `GET /resources/{id do recurso}/subdivisions` responde em JSON.

### Testes

Testes unitários e de integração ([PHPUnit](https://phpunit.de/)):

```
$ ./run test
```

Testes de aceitação no navegador ([Codeception](https://codeception.com/) + Selenium):

```
$ ./run test:browser
```

### Qualidade de código

[PHPCS](https://github.com/PHPCSStandards/PHP_CodeSniffer/)

```
$ ./run phpcs
```

[PHPStan](https://phpstan.org/)

```
$ ./run phpstan
```

### Parar a aplicação

```
$ ./run down
```
