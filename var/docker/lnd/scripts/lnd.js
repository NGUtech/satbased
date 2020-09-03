import createLnRpc, {createInvoicesRpc, createRouterRpc} from '@radar/lnrpc';

export default class {
  static lnrpc;
  static invoicesrpc;
  static routerrpc;

  /**
   * Initialize gRPC clients for the main server and all sub-servers
   * @param config The RPC client connection configuration
   */
  static async init(config) {
    this.lnrpc = await createLnRpc(config);
    this.invoicesrpc = await createInvoicesRpc(config);
    this.routerrpc = await createRouterRpc(config);
  }
}