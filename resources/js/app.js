import './bootstrap';

import {browserSupportsWebAuthn, startAuthentication, startRegistration} from '@simplewebauthn/browser'

import Alpine from 'alpinejs';

window.Alpine = Alpine;


document.addEventListener('alpine:init',()=>{
    Alpine.data('registerPasskey',()=>({
        form:{
            name:'',
            passkey:''
        },
        errors:null,
        notification:false,



        async register(form){
            this.errors = null

            if (!browserSupportsWebAuthn()) {
                return ;
            }

            const options = await axios.get("/api/passkeys/register");

            try {

                const passkey = await startRegistration({optionsJSON:options.data,useAutoRegister:true})
                console.log('passkey',passkey)
                this.form.passkey = JSON.stringify(passkey);

                axios.post(route('passkeys.store',[],false),this.form).then(res=>{
                    console.log('res',res)
                    if(res.status==201){
                        // this.$dispatch('passkey-saved')
                        this.notification = true
                        window.location.href=route('profile.edit')
                    }
                }).catch(e=>{
                    console.log('e',e)
                    if(e.response.status==422){
                        this.errors = e.response.data.errors
                    }
                })

            } catch (error) {
                console.log('error',error)
                this.errors = {
                    name:['Passkey creation failed, please try again']
                }
                return

            }

            // return
            // const passkey = await startRegistration(options.data)


            // form.addEventListener('formData',({formData})=>{

            //     formData.set('passkey',JSON.stringify(passkey));
            // })

            // form.submit()
        }
    }));

    Alpine.data('authenticatePasskey',()=>({
        form:{
            answer:''
        },
        async authenticate(form){
         const options = await axios.get("/api/passkeys/authenticate");

         console.log('auth option',options.data)
         const answer = await startAuthentication(options.data)
         this.form.answer = JSON.stringify(answer)

         axios.post("passkeys/authenticate",this.form).then(res=>{
            console.log('res',res)
            window.location.href="/dashboard"

         }).catch(e=>{
            console.log('error',e)
         }).finally(()=>{
            window.location.href="/dashboard"
         })


        }
    }));
});

Alpine.start();
