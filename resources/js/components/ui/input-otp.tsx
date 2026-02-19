import { useContext, type ComponentProps, type FC } from "react";
import {
    OTPInput,
    OTPInputContext,
    type OTPInputProps,
} from "input-otp";
import { MinusIcon } from "lucide-react";
import { cn } from "@/lib/utils";

interface InputOTPBaseProps {
    containerClassName?: string;
}
type InputOTPProps = OTPInputProps & InputOTPBaseProps;

const InputOTP: FC<InputOTPProps> = ({
    className,
    containerClassName,
    ...props
}) => {
    return (
        <OTPInput
            data-slot="input-otp"
            containerClassName={cn(
                "flex items-center gap-2 has-disabled:opacity-50",
                containerClassName,
            )}
            className={cn("disabled:cursor-not-allowed", className)}
            {...props}
        />
    );
};

interface InputOTPGroupProps extends ComponentProps<"div"> {}

const InputOTPGroup: FC<InputOTPGroupProps> = ({
    className,
    ...props
}) => {
    return (
        <div
            data-slot="input-otp-group"
            className={cn("flex items-center", className)}
            {...props}
        />
    );
};

interface InputOTPSlotProps extends ComponentProps<"div"> {
    index: number;
}

const InputOTPSlot: FC<InputOTPSlotProps> = ({
    index,
    className,
    ...props
}) => {
    const inputOTPContext = useContext(OTPInputContext);
    const { char, hasFakeCaret, isActive } =
        inputOTPContext?.slots[index] ?? {};

    return (
        <div
            data-slot="input-otp-slot"
            data-active={isActive}
            className={cn(
                "data-[active=true]:border-ring data-[active=true]:ring-ring/50 data-[active=true]:aria-invalid:ring-destructive/20 dark:data-[active=true]:aria-invalid:ring-destructive/40 aria-invalid:border-destructive data-[active=true]:aria-invalid:border-destructive dark:bg-input/30 border-input relative flex h-9 w-9 items-center justify-center border-y border-r text-sm shadow-xs transition-all outline-none first:rounded-l-md first:border-l last:rounded-r-md data-[active=true]:z-10 data-[active=true]:ring-[3px]",
                className,
            )}
            {...props}
        >
            {char}
            {hasFakeCaret ? (
                <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div className="animate-caret-blink bg-foreground h-4 w-px duration-1000" />
                </div>
            ) : null}
        </div>
    );
};

interface InputOTPSeparatorProps extends ComponentProps<"div"> {}

const InputOTPSeparator: FC<InputOTPSeparatorProps> = (props) => {
    return (
        <div data-slot="input-otp-separator" role="separator" {...props}>
            <MinusIcon />
        </div>
    );
};

export { InputOTP, InputOTPGroup, InputOTPSlot, InputOTPSeparator };
