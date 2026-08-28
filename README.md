# Sistema de Gestão e Reserva de Ambientes Acadêmicos

## Visão Geral do Projeto

O **Reservas Acadêmicas** é um sistema para gerenciamento e reserva de ambientes e recursos acadêmicos, como salas, laboratórios, auditórios, bancadas, computadores e equipamentos.

A plataforma permite consultar a disponibilidade dos recursos, solicitar e cancelar reservas, acompanhar aprovações e gerenciar ambientes de acordo com o perfil do usuário. Também oferece controle de acesso, prevenção de conflitos de agenda e suporte à reserva de ambientes completos ou de suas subdivisões.

## Perfis de Usuário

- **Discentes:** alunos que podem consultar, solicitar e cancelar reservas.
- **Docentes:** professores que podem solicitar, cancelar, analisar e aprovar reservas.
- **Técnicos:** servidores com acesso administrativo para aprovação de solicitações e gerenciamento dos ambientes e recursos.

## Requisitos Funcionais

### RF01 - Fazer Login

**Descrição:** Permite que o usuário acesse o sistema utilizando seu e-mail acadêmico e senha, disponibilizando as funcionalidades correspondentes ao seu perfil.

**Atores:** Discentes, Docentes e Técnicos.

### RF02 - Solicitar Nova Reserva

**Descrição:** Permite solicitar a utilização de um ambiente, subdivisão ou equipamento para uma determinada data, horário e atividade.

A reserva poderá contemplar o ambiente completo ou apenas uma de suas subdivisões, conforme a configuração do recurso.

**Atores:** Discentes, Docentes e Técnicos.

### RF03 - Assinar Termo de Uso

**Descrição:** Permite que o usuário registre sua concordância com as regras institucionais e assuma responsabilidade pelo uso do recurso solicitado.

**Atores:** Discentes, Docentes e Técnicos.

### RF04 - Analisar Solicitações

**Descrição:** Permite visualizar os detalhes de uma solicitação pendente e registrar sua aprovação ou rejeição.

**Atores:** Docentes e Técnicos.

### RF05 - Cancelar Reserva

**Descrição:** Permite cancelar uma reserva pendente ou confirmada, liberando o período reservado para novas solicitações.

**Atores:** Discentes, Docentes e Técnicos.

### RF06 - Cadastrar Item

**Descrição:** Permite cadastrar novos ambientes, equipamentos ou subdivisões para utilização no sistema.

**Atores:** Técnicos.

### RF07 - Editar Item

**Descrição:** Permite atualizar informações, regras, disponibilidade e demais características de um ambiente ou recurso existente.

**Atores:** Técnicos.

### RF08 - Inativar Item

**Descrição:** Permite suspender temporariamente a disponibilidade de um ambiente ou recurso para novas reservas, mantendo seu histórico de utilização.

**Atores:** Técnicos.

### RF09 - Desativar Item

**Descrição:** Permite retirar permanentemente um ambiente ou recurso da disponibilidade para novas reservas, preservando seu histórico no sistema.

**Atores:** Técnicos.

### RF10 - Excluir Item

**Descrição:** Permite remover definitivamente um ambiente ou recurso que nunca tenha sido utilizado em uma reserva.

**Atores:** Técnicos.

## Requisitos Não Funcionais

### Requisitos de Processo

#### RNF01 - Desenvolvimento

O sistema será desenvolvido utilizando uma abordagem ágil e iterativa, permitindo a evolução gradual das funcionalidades e ajustes baseados no feedback dos usuários.

### Requisitos de Produto

#### Desempenho e Disponibilidade

##### RNF02 - Tempo de Resposta

As consultas de disponibilidade e as operações de confirmação de reserva devem apresentar tempo de resposta inferior a 3 segundos em condições normais de conexão.

##### RNF03 - Concorrência

O sistema deve suportar múltiplos usuários realizando consultas e solicitações simultaneamente, principalmente durante períodos de maior demanda, como o início do semestre letivo.

##### RNF04 - Disponibilidade

O sistema deve apresentar disponibilidade mínima de 99%, exceto durante períodos de manutenção programada.

#### Segurança

##### RNF05 - Autenticação Institucional

O sistema deve, preferencialmente, permitir integração com serviços institucionais de autenticação, como LDAP ou Active Directory, utilizando as credenciais acadêmicas já existentes.

##### RNF06 - Controle de Acesso

O sistema deve implementar controle de acesso baseado em perfis de usuário, garantindo que funcionalidades administrativas sejam acessíveis apenas por usuários autorizados.

##### RNF07 - Auditoria

Ações críticas, como aprovação, rejeição, cancelamento e exclusão de recursos, devem gerar registros de auditoria contendo o usuário responsável, data, horário e ação realizada.

### Usabilidade

#### RNF08 - Responsividade

A interface deve se adaptar a diferentes tamanhos de tela, permitindo o uso do sistema em computadores, tablets e smartphones sem perda significativa de funcionalidade.

#### RNF09 - Identidade Visual

A interface deve seguir o manual de identidade visual da instituição, incluindo padrões de cores, tipografia, logotipos e demais elementos definidos pela UTFPR.

#### RNF10 - Feedback do Sistema

O sistema deve apresentar mensagens claras e imediatas para informar o resultado das ações realizadas pelo usuário.

Exemplos:

- Reserva solicitada com sucesso.
- Reserva cancelada com sucesso.
- Horário indisponível para reserva.
- Antecedência mínima não respeitada.

#### RNF11 - Acessibilidade

O sistema deve seguir, sempre que possível, as diretrizes de acessibilidade da WCAG e oferecer compatibilidade com tecnologias assistivas.

Também poderá integrar ferramentas de acessibilidade, como o VLibras.

### Requisitos Externos

#### RNF12 - Conformidade com a LGPD

O tratamento de dados pessoais deve estar em conformidade com a Lei Geral de Proteção de Dados (LGPD), garantindo segurança, privacidade e utilização adequada das informações dos usuários.

#### RNF13 - Regimento Institucional

As regras de negócio relacionadas a prazos, prioridades, permissões e utilização dos ambientes devem estar de acordo com os regulamentos internos da instituição.

#### RNF14 - Compatibilidade

O sistema deve ser compatível com versões atuais dos principais navegadores:

- Google Chrome
- Mozilla Firefox
- Microsoft Edge
- Safari

## Fluxo de Reserva

O processo de reserva deve seguir, de forma geral, as seguintes etapas:

1. Seleção do ambiente ou recurso.
2. Seleção de uma subdivisão, quando aplicável.
3. Escolha da data.
4. Escolha do horário.
5. Seleção da categoria da atividade.
6. Aceite do termo de uso.
7. Envio da solicitação.
8. Análise da solicitação, quando necessária.
9. Aprovação ou rejeição da reserva.

## Estrutura dos Ambientes

Os ambientes cadastrados poderão possuir subdivisões ou recursos associados.

Exemplo:
    Laboratório de Informática
    ├── Computador 01
    ├── Computador 02
    ├── Computador 03
    └── Computador 04

Outro exemplo:
    Laboratório de Eletrônica
    ├── Bancada 01
    ├── Bancada 02
    └── Bancada 03

O sistema deverá permitir a reserva de uma subdivisão específica ou do ambiente completo, quando essa opção estiver disponível.

Quando o ambiente completo estiver reservado, suas subdivisões não poderão receber reservas no mesmo período.

Da mesma forma, o ambiente completo não poderá ser reservado caso uma de suas subdivisões já possua uma reserva para o mesmo período.

## Objetivos

O sistema tem como principais objetivos:

- Centralizar o gerenciamento de reservas acadêmicas.
- Facilitar a consulta e utilização dos ambientes institucionais.
- Evitar conflitos de horários e reservas simultâneas.
- Organizar o processo de solicitação e aprovação.
- Garantir controle de acesso e rastreabilidade das operações.
- Melhorar o aproveitamento dos espaços e recursos acadêmicos.
