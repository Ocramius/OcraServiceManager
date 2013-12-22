# Upgrade Notes

### Breakages in version [2.1.0](https://github.com/Ocramius/OcraServiceManager/issues?milestone=3&state=open)

 - Config key `ocra_service_manager.logged_service_manager` is now `true` by default. If you run OcraServiceManager
   also in a production environment, you should disable it. [#29](https://github.com/Ocramius/OcraServiceManager/pull/29)