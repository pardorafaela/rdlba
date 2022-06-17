var app = new Vue({
    el: '#app',
    vuetify: new Vuetify(),
    data: {
        e6: 1,
        sgbds: [{ name: 'PostgreSQL', alias: 'Postgres' }, { name: 'MySQL', alias: 'Mysql' }],
        file: null,
        loading3: false,
        loader: null,
        sgbd: '',
        host: '',
        port: '',
        user: '',
        password: '',
        database: '',
        schema: '',
        schemas: [],
        problemas: [],
        tables: [],
        selTodos: false,
        tableAtualSource: '',
        tableAtualTarget: '',
        fieldsAtualTarget: [],
        fieldsTarget: [],
        fieldsAtualSource: [],
        fieldsSource: [],
        fieldAtualSource: '',
        fieldAtualTarget: '',
        relatorio: [],
        pdfUrl: '',
        singleSelect: false,
        problemasSel: [],
        dbinfo: [],
        expanded: [],
        headersProblema: [{
            'text': 'Problema',
            'value': 'id'
        },
        {
            'text': 'Descrição',
            'value': 'descricao'
        }
        ],
        headersRelatorio: [{
            'text': 'Problema',
            'value': 'description',
        }],
        driver: {
            'Postgres': 'pgsql',
            'Mysql': 'mysql'
        },

        desserts: [],
        headers: [{
            text: '',
            value: 'position',
        },
        {
            text: 'Chave Estrangeira',
            value: 'name',
        },
        {
            text: 'Excluir',
            value: 'excluir',
        }
        ],
    },
    methods: {
        selecionaTodos(event) {
            console.log(event)
        },
        checaCampos(campos, msg) {
            let message = false
            campos.forEach(el => {
                if (el == "" || el == null || el == false || typeof el == 'undefined')
                    message = true
            })

            if (message) {
                this.alerta({ status: "warning", report: msg })
                return true
            }
            return false
        },
        limpaCamposFK() {
            this.tableAtualSource = ""
            this.tableAtualTarget = ""
            this.fieldAtualSource = ""
            this.fieldAtualTarget = ""
            this.fieldsSource = ""
            this.fieldsTarget = ""
        },
        conectar() {
            let data = new FormData();

            if (this.checaCampos([this.sgbd, this.host, this.port, this.user, this.password, this.database], "Preencher todos os campos da conexão"))
                return;

            data.append('funcao', 'setConfig')
            data.append('sgbd', this.sgbd)
            data.append('driver', this.driver[this.sgbd])
            data.append('host', this.host)
            data.append('port', this.port)
            data.append('user', this.user)
            data.append('pass', this.password)
            data.append('db', this.database)

            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    if (result.code == 200) {
                        console.log(result)
                        this.getSchemas()
                    }
                    else this.alerta(result)

                })
                .catch(error => {
                    console.log(error)
                });
        },
        getSchemas() {
            let data = new FormData();
            data.append('funcao', 'getSchemas')

            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    if (result.code == 200) {
                        this.schemas = result.schemas
                        this.getProblemas()
                    } else this.alerta(result)
                })
                .catch(error => {
                    console.log(error)
                });
        },
        sendFile() {

            const l = this.loader
            this[l] = !this[l]

            if (!this.file) {
                this.alerta({ status: 'warning', report: `Nenhum arquivo adicionado` })
                return true;
            }

            if (!this.file.name.match(/^Problema[0-9]+/gm)) {
                this.alerta({ status: 'warning', report: `Nome do(s) Arquivo(s) inválido(s): ${this.file.name}` })
                return;
            }

            let data = new FormData();
            data.append('funcao', 'sendFile')
            data.append('file', this.file)


            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    this.alerta(result)
                })
                .catch(error => {
                    console.log(error)
                })
                .finally(() => {
                    this[l] = false
                    this.loader = null
                });
        },
        setSchema() {
            let data = new FormData();
            if (this.checaCampos([this.schema], "Preencher o schema"))
                return

            data.append('funcao', 'setSchema')
            data.append('schema', this.schema)

            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    if (result.code == 200)
                        this.setProblemas();
                    else this.alerta(result)
                })
                .catch(error => {
                    console.log(error)
                });
        },
        getProblemas() {
            let data = new FormData();
            data.append('funcao', 'getProblemas')
            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    if (result.code == 200) {
                        this.problemas = result.problemas.sort((e1, e2) => {
                            if (e1.id > e2.id)
                                return 1
                            else return -1
                        })

                        this.e6 = 2;
                    } else this.alerta(result)
                })
                .catch(error => {
                    console.log(error)
                });
        },
        getDBInfo() {
            let data = new FormData();
            data.append('funcao', 'getDBInfo')
            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    if (result.code == 200) {
                        this.dbinfo = result.tabelas;
                        this.e6 = 3;
                    } else this.alerta(result)
                })
                .catch(error => {
                    console.log(error)
                });
        },
        changeTableSource() {
            this.fieldsAtualSource = []
            this.fieldsSource = []
            this.dbinfo.forEach(el => {
                if (this.tableAtualSource == el.name) {
                    el.fields.forEach(fd => {
                        fd.name = fd.name.match(/:/g) ? fd.name : `${fd.name}:${fd.type}`
                        this.fieldsAtualSource.push(fd)
                    });
                }
            });
        },
        changeTableTarget() {
            this.fieldsAtualTarget = []
            this.fieldsTarget = []
            this.dbinfo.forEach(el => {
                if (this.tableAtualTarget == el.name) {
                    el.fields.forEach(fd => {
                        fd.name = fd.name.match(/:/g) ? fd.name : `${fd.name}:${fd.type}`
                        this.fieldsAtualTarget.push(fd)
                    });
                }
            });
        },
        addItem(item, $tipo) {
            if ($tipo == 'target') this.fieldsTarget.push(item)
            if ($tipo == 'source') this.fieldsSource.push(item)

        },
        addFK() {
            let fk = `${this.tableAtualSource} (${this.fieldsSource}) = ${this.tableAtualTarget} (${this.fieldsTarget})`;
            let add = !this.desserts.find(el => el.name == fk);

            if (this.tableAtualSource == this.tableAtualTarget)
                add = false
            this.dbinfo.forEach(el => {
                if (el.name == this.tableAtualSource) {
                    el.constraints.forEach(ctr => {
                        if (ctr.type == 'foreign key' && ctr.reference.name == this.tableAtualTarget) {
                            // for (let index = 0; index < ctr.fields.length; index++) {
                            //     fd = this.getField(ctr.fields[index], el.name);
                            //     fdReference = this.getField(ctr.reference.fields[index], ctr.reference.name);
                            // }
                            add = false;
                        }
                    });
                }

            });
            if (add) {
                this.desserts.push({
                    'name': fk,
                    'position': this.desserts.length + 1
                })
            } else {
                this.alerta({ status: "warning", report: "Por favor, revisar a chave estrangeira." })
            }


            this.limpaCamposFK();
        },
        deleteItem(item, $tipo) {
            if ($tipo == 'target')
                this.fieldsTarget.splice(this.fieldsTarget.indexOf(item), 1);

            if ($tipo == 'source')
                this.fieldsSource.splice(this.fieldsSource.indexOf(item), 1);
        },
        deleteFK(item) {
            this.desserts.splice(this.desserts.indexOf(item), 1);
        },
        setProblemas() {
            let data = new FormData();
            if (this.checaCampos(this.problemasSel, "Selecionar algum problema."))
                return

            data.append('funcao', 'setProblemas')
            data.append('problemas', JSON.stringify(this.problemasSel))
            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    if (result.code == 200) {
                        fkNaoDeclarada = false;
                        this.problemasSel.forEach(el => {
                            if (el.id.match(/Problema3/i)) {
                                fkNaoDeclarada = true;
                            }
                        });
                        if (fkNaoDeclarada) {
                            this.getDBInfo()
                            this.e6 = 3
                        } else {
                            this.rdlba()
                        }
                    } else this.alerta(result)
                })
                .catch(error => {
                    console.log(error)
                });
        },
        setForeign() {
            let data = new FormData();
            data.append('funcao', 'setForeign')
            data.append('foreign', JSON.stringify(this.desserts))
            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    if (result.code == 200)
                        this.rdlba();
                    else this.alerta(result)
                })
                .catch(error => {

                    console.log(error)
                });
        },
        rdlba() {
            let data = new FormData();
            data.append('funcao', 'rdlba')
            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    if (result.code == 200) {
                        this.getRelatorio()
                        this.e6 = 4
                    } else this.alerta(result)
                })
                .catch(error => {
                    console.log(error)
                });
        },
        baixaPDF() {
            window.open(this.pdfUrl, '_blank');
        },
        alerta(alerta) {
            swal(
                '',
                alerta.report ? alerta.report : alerta.title,
                alerta.status
            )
        },
        getRelatorio() {
            let data = new FormData();
            data.append('funcao', 'getRelatorio')
            fetch(`api/rdlba.php`, {
                method: 'POST',
                body: data
            })
                .then(response => response.json())
                .then(result => {
                    if (result.code == 200) {
                        this.e6 = 4
                        this.relatorio = result.relatorio;
                        this.pdfUrl = result.path;
                    } else this.alerta(result)
                })
                .catch(error => {
                    console.log(error)
                });
        },
    },
});