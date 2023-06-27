import { Decimal } from "@cosmjs/math";
import { GasPrice, SigningStargateClient, coins } from "@cosmjs/stargate";
import { Window as KeplrWindow } from "@keplr-wallet/types";
import { TxRaw } from "cosmjs-types/cosmos/tx/v1beta1/tx";
import { aminoTypes, registry, tx } from "kujira.js";
import { useState } from "react";
import { render } from "react-dom";
import Logo from "./components/Logo";

declare global {
  interface Window extends KeplrWindow {}
}

const DENOM =
  "factory/kujira1qk00h5atutpsv900x202pxx42npjr9thg58dnqpa72f2p7m2luase444a7/uusk";

const CHAIN_INFO = {
  chainId: "kaiyo-1",
  chainName: "Kujira",
  rpc: "https://rpc-kujira.mintthemoon.xyz",
  rest: "https://lcd-kujira.mintthemoon.xyz",
  bip44: {
    coinType: 118,
  },
  bech32Config: {
    bech32PrefixAccAddr: "kujira",
    bech32PrefixAccPub: "kujira" + "pub",
    bech32PrefixValAddr: "kujira" + "valoper",
    bech32PrefixValPub: "kujira" + "valoperpub",
    bech32PrefixConsAddr: "kujira" + "valcons",
    bech32PrefixConsPub: "kujira" + "valconspub",
  },
  currencies: [
    {
      coinDenom: "KUJI",
      coinMinimalDenom: "ukuji",
      coinDecimals: 6,
      coinGeckoId: "kujira",
    },
    {
      coinDenom: "USK",
      coinMinimalDenom: DENOM,
      coinDecimals: 6,
      coinGeckoId: "usk",
    },
  ],
  feeCurrencies: [
    {
      coinDenom: "USK",
      coinMinimalDenom: DENOM,
      coinDecimals: 6,
      coinGeckoId: "usk",
    },
  ],
  stakeCurrency: {
    coinDenom: "KUJI",
    coinMinimalDenom: "ukuji",
    coinDecimals: 6,
    coinGeckoId: "kujira",
  },
  coinType: 118,
  gasPriceStep: {
    low: 0.0015,
    average: 0.002,
    high: 0.003,
  },
};
const encode = (bytes: Uint8Array): string =>
  Buffer.from(bytes).toString("base64");

const Component: React.FC<{ to: string; amount: string }> = (props) => {
  const amount = parseFloat(props.amount);
  const recipient = props.to;
  const [signed, setSigned] = useState("");

  const submit = async (e) => {
    e.preventDefault();

    if (!window.keplr) {
      alert("Please install keplr extension");
    } else {
      await window.keplr.experimentalSuggestChain(CHAIN_INFO);

      await window.keplr.enable(CHAIN_INFO.chainId);
      const offlineSigner = window.keplr.getOfflineSigner(CHAIN_INFO.chainId);

      const accounts = await offlineSigner.getAccounts();

      const gasPrice = new GasPrice(
        Decimal.fromUserInput("0.00150", 18),
        DENOM
      );

      const client = await SigningStargateClient.connectWithSigner(
        "https://rpc-kujira.mintthemoon.xyz",
        offlineSigner,
        {
          registry,
          gasPrice,
          aminoTypes: aminoTypes("kujira"),
        }
      );

      const amountInt = Math.floor(amount * 10 ** 6);

      const msg = tx.bank.msgSend({
        amount: coins(amountInt, DENOM),
        from_address: accounts[0].address,
        to_address: recipient,
      });

      const txRaw = await client.sign(
        accounts[0].address,
        [msg],
        {
          // This is ignored by Keplr for now
          amount: coins(0, DENOM),
          gas: "100000",
        },
        ""
      );

      const txBytes = TxRaw.encode(txRaw).finish();

      setSigned(encode(txBytes));
    }
  };
  return (
    <div className="kujira-usk-payment">
      <textarea value={signed} name="usk_tx" />
      {signed !== "" ? (
        <>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
            <path
              fill="currentColor"
              d="M235.3 331.3C229.1 337.6 218.9 337.6 212.7 331.3L148.7 267.3C142.4 261.1 142.4 250.9 148.7 244.7C154.9 238.4 165.1 238.4 171.3 244.7L224 297.4L340.7 180.7C346.9 174.4 357.1 174.4 363.3 180.7C369.6 186.9 369.6 197.1 363.3 203.3L235.3 331.3zM512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 32C132.3 32 32 132.3 32 256C32 379.7 132.3 480 256 480C379.7 480 480 379.7 480 256C480 132.3 379.7 32 256 32z"
            />
          </svg>
          <p>Your payment has been authorised</p>
        </>
      ) : (
        <p>Spend USK direct from your wallet</p>
      )}
      <button onClick={submit} className={signed !== "" ? "outline" : ""}>
        {signed !== ""
          ? "Re-authorize"
          : `Authorize ${props.amount} USK Payment`}
      </button>
      <Logo />
    </div>
  );
};

export default (el, dataset) => render(<Component {...dataset} />, el);
